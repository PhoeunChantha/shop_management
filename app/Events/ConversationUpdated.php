<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Conversation meta changed (status, assignee, read receipts) — lets both sides
 * refresh headers/badges without a new message.
 */
class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $conversation  serialized conversation summary
     * @param  string  $reason  status|assigned|read
     */
    public function __construct(
        public readonly array $conversation,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.'.$this->conversation['id']),
            new PrivateChannel('admin.chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.conversation';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation['id'],
            'conversation' => $this->conversation,
            'reason' => $this->reason,
        ];
    }
}
