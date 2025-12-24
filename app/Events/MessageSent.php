<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * We pass the Message and the recipient User directly to avoid
     * any database queries during the broadcasting process.
     */
    public function __construct(
        public Message $message,
        protected User $recipient,
    ) {
        // Ensure the user relationship is loaded for the frontend display
        if (!$this->message->relationLoaded("user")) {
            $this->message->load("user");
        }
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            "message" => $this->message,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->recipient->id}.chats")];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return "MessageSent";
    }
}
