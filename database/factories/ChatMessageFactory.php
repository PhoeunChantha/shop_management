<?php

namespace Database\Factories;

use App\Enums\ChatSenderRole;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'sender_role' => ChatSenderRole::Customer,
            'body' => $this->faker->sentence(),
            'read_at' => null,
        ];
    }

    public function fromStaff(): static
    {
        return $this->state(fn () => ['sender_role' => ChatSenderRole::Staff]);
    }
}
