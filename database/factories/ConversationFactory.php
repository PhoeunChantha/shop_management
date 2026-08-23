<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assigned_to' => null,
            'subject' => null,
            'status' => ConversationStatus::Open,
            'last_message_preview' => null,
            'last_message_at' => null,
            'customer_unread' => 0,
            'admin_unread' => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ConversationStatus::Closed, 'closed_at' => now()]);
    }
}
