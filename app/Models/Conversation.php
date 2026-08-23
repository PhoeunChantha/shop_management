<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One support thread per customer. Reused forever: closing it only flags it,
 * and the next customer message reopens it.
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'subject',
        'status',
        'last_message_preview',
        'last_message_at',
        'customer_unread',
        'admin_unread',
        'closed_at',
    ];

    protected $casts = [
        'status' => ConversationStatus::class,
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'customer_unread' => 'integer',
        'admin_unread' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ConversationStatus::Open;
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ConversationStatus::Open);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            filled($status) && ConversationStatus::tryFrom((string) $status),
            fn (Builder $q) => $q->where('status', $status),
        );
    }

    public function scopeAssignedTo(Builder $query, ?int $userId): Builder
    {
        return $query->when($userId, fn (Builder $q) => $q->where('assigned_to', $userId));
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('admin_unread', '>', 0);
    }

    /**
     * Filter by customer name / email or message preview. Skips when blank.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term): void {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

            $q->where(function (Builder $inner) use ($like): void {
                $inner->where('last_message_preview', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhereHas('customer', function (Builder $c) use ($like): void {
                        $c->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        });
    }
}
