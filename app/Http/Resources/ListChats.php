<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ListChats extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = Auth::user();

        // Find the other participant in the private chat
        $otherUser = $this->users->first(function ($user) use ($currentUser) {
            return $user->id !== $currentUser->id;
        });

        return [
            "id" => $this->id,
            "title" => $otherUser ? $otherUser->nickname : "Unknown User",
            "unread" => $this->my_unread ?? 0,
            "last_message" => $this->last_message,
            "time" => $this->updated_at->diffForHumans(),
            "users" => $this->users->map(
                fn($u) => [
                    "id" => $u->id,
                    "nickname" => $u->nickname,
                ],
            ),
            "created_at" => $this->created_at->format("Y-m-d H:i:s"),
            "updated_at" => $this->updated_at->format("Y-m-d H:i:s"),
        ];
    }
}
