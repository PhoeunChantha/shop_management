<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Live chat thread: the owning customer or any staff member allowed to view chats.
Broadcast::channel('chat.conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::query()->select(['id', 'user_id'])->find((int) $conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->isOwnedBy($user) || $user->can('view chats');
});

// Admin inbox feed: every new message / conversation change across all threads.
Broadcast::channel('admin.chat', fn (User $user) => $user->can('view chats'));
