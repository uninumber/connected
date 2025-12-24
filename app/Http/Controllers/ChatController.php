<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ListChats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\MessageSent;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's chats.
     */
    public function index()
    {
        $user = Auth::user();
        $chats = $user->chats()->with("users")->get();
        return ListChats::collection($chats);
    }

    public function store(Request $request)
    {
        $request->validate([
            "userId" => "required|exists:users,id",
        ]);

        $user = Auth::user();
        $targetUser = User::findOrFail($request->userId);

        $chat = $user->chats()->create([
            "last_message" => "Start chatting with " . $targetUser->nickname,
        ]);

        DB::table("chat_user")->insert([
            ["chat_id" => $chat->id, "user_id" => $user->id],
            ["chat_id" => $chat->id, "user_id" => $targetUser->id],
        ]);

        return response()->json($chat);
    }

    /**
     * Display the specified chat.
     */
    public function show($id)
    {
        $user = Auth::user();
        $chat = $user
            ->chats()
            ->with(["users", "messages"])
            ->findOrFail($id);
        return response()->json($chat);
    }

    /**
     * Search for chats and users.
     */
    public function search(Request $request)
    {
        $query = $request->query("q");
        $user = Auth::user();

        if (empty($query)) {
            return response()->json(["chats" => [], "users" => []]);
        }

        // Search in existing chats (by other user's nickname or group name)
        $chats = $user
            ->chats()
            ->whereHas("users", function ($q) use ($query, $user) {
                $q->where("nickname", "like", "%{$query}%")->where(
                    "users.id",
                    "!=",
                    $user->id,
                );
            })
            ->get();

        // Search for new users to start a chat with
        $users = User::where("nickname", "like", "%{$query}%")
            ->where("id", "!=", $user->id)
            ->limit(10)
            ->get();

        return response()->json([
            "chats" => ListChats::collection($chats),
            "users" => $users,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            "chat_id" => ["required", "exists:chats,id"],
            "text" => ["required", "string", "max:255"],
        ]);

        $chatId = $request->input("chat_id");
        $text = $request->input("text");

        $chat = $user->chats()->findOrFail($chatId);
        $message = $chat->messages()->create([
            "id" => Str::uuid()->toString(),
            "user_id" => $user->id,
            "text" => $text,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $message->load("user");

        return response()->json(["message" => $message]);
    }

    public function listMessages($chatId)
    {
        $user = Auth::user();
        $messages = $user
            ->chats()
            ->findOrFail($chatId)
            ->messages()
            ->with("user")
            ->orderBy("created_at", "asc")
            ->get();

        return response()->json($messages);
    }
}
