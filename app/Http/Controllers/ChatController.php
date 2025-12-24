<?php

namespace App\Http\Controllers;

use App\Models\Chat;
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
        $chats = Chat::query()
            ->join("chat_user as cu", "chats.id", "=", "cu.chat_id")
            ->where("cu.user_id", $user->id)
            ->orderBy("chats.updated_at", "desc")
            ->with("users")
            ->select("chats.*", "cu.unread as my_unread")
            ->get();
        return ListChats::collection($chats);
    }

    public function store(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id",
        ]);

        $user = Auth::user();
        if ($user === $request->user_id) {
            return response()->json(
                ["error" => "Cannot chat with yourself"],
                400,
            );
        }

        $targetUser = User::findOrFail($request->user_id);

        $existingChat = $user
            ->chats()
            ->whereHas("users", function ($q) use ($targetUser) {
                $q->where("users.id", $targetUser->id);
            })
            ->exists();

        if ($existingChat) {
            $chat = $user
                ->chats()
                ->whereHas("users", function ($q) use ($targetUser) {
                    $q->where("users.id", $targetUser->id);
                })
                ->first();
            return response()->json([
                "message" => "Chat already exists",
                "chat" => new ListChats($chat),
            ]);
        }

        $chat = Chat::create([
            "last_message" => "Start chatting with " . $targetUser->nickname,
            "id" => Str::uuid()->toString(),
        ]);

        DB::table("chat_user")->insert([
            ["chat_id" => $chat->id, "user_id" => $user->id],
            ["chat_id" => $chat->id, "user_id" => $targetUser->id],
        ]);

        // Reload the chat with users to ensure the resource has everything it needs
        $chat->load("users");

        return response()->json([
            "message" => "Have a fun chatting!",
            "chat" => new ListChats($chat),
        ]);
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

        DB::table("chat_user")
            ->where("chat_id", $chat->id)
            ->where("user_id", $user->id)
            ->update(["unread" => 0]);

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

        $otherUser = $chat
            ->users()
            ->whereNot("users.id", $user->id)
            ->select("users.id", "users.nickname")
            ->first();

        $message = $chat->messages()->create([
            "id" => Str::uuid()->toString(),
            "user_id" => $user->id,
            "text" => $text,
        ]);

        $chat->last_message = $user->nickname . ": " . $text;
        $chat->save();

        DB::table("chat_user")
            ->where("chat_id", $chatId)
            ->where("user_id", $otherUser->id)
            ->increment("unread", 1);

        broadcast(new MessageSent($message, $otherUser))->toOthers();

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
