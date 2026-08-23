<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatSenderRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_role',
        'body',
        'product_id',
        'read_at',
    ];

    protected $casts = [
        'sender_role' => ChatSenderRole::class,
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The product the message is about ("Ask about this product"), if any.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_role === ChatSenderRole::Customer;
    }
}
