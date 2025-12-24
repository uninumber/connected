<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel("App.Models.User.{id}", function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel("chat.{chatId}", function ($user, $chatId) {
    return $user->chats()->where("chats.id", $chatId)->exists();
});

Broadcast::channel("user.{userId}.chats", function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});
