<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ChatSenderRole;
use App\Enums\ConversationStatus;
use App\Events\ChatMessageSent;
use App\Events\ConversationUpdated;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared live-chat domain logic (storefront + admin). One conversation per
 * customer; staff reply from the admin inbox. Every mutation broadcasts over
 * Reverb synchronously, and a broadcast failure is logged — never thrown — so the
 * message still persists and the UI's polling fallback picks it up.
 */
final class ChatService
{
    public const PAGE_SIZE = 30;

    /**
     * The customer's conversation, created on first use.
     */
    public function conversationFor(User $customer): Conversation
    {
        return Conversation::query()->firstOrCreate(
            ['user_id' => $customer->id],
            ['status' => ConversationStatus::Open],
        );
    }

    /**
     * Find the customer's conversation without creating one.
     */
    public function existingConversationFor(User $customer): ?Conversation
    {
        return Conversation::query()->where('user_id', $customer->id)->first();
    }

    /**
     * Store a message from either side, bump the other side's unread counter,
     * reopen a closed thread, then broadcast.
     */
    public function sendMessage(Conversation $conversation, User $sender, string $body, ChatSenderRole $role, ?int $productId = null): ChatMessage
    {
        $body = trim($body);

        $message = DB::transaction(function () use ($conversation, $sender, $body, $role, $productId): ChatMessage {
            /** @var Conversation $locked */
            $locked = Conversation::query()->lockForUpdate()->findOrFail($conversation->id);

            $message = $locked->messages()->create([
                'sender_id' => $sender->id,
                'sender_role' => $role,
                'body' => $body,
                'product_id' => $productId,
            ]);

            $updates = [
                'last_message_preview' => Str::limit($body, 150, '...'),
                'last_message_at' => $message->created_at,
            ];

            if ($role === ChatSenderRole::Customer) {
                $updates['admin_unread'] = $locked->admin_unread + 1;
            } else {
                $updates['customer_unread'] = $locked->customer_unread + 1;
                if (! $locked->assigned_to) {
                    $updates['assigned_to'] = $sender->id;
                }
            }

            // Any new message into a closed thread reopens it.
            if (! $locked->isOpen()) {
                $updates['status'] = ConversationStatus::Open;
                $updates['closed_at'] = null;
            }

            $locked->update($updates);
            $conversation->setRawAttributes($locked->getAttributes(), true);

            return $message;
        });

        $message->setRelation('sender', $sender);
        $message->load('product');
        $conversation->load(['customer', 'assignee']);

        $this->broadcast(new ChatMessageSent(
            $this->serializeMessage($message),
            $this->serializeConversation($conversation),
        ));

        return $message;
    }

    /**
     * Mark everything the viewer hasn't seen as read and clear their counter.
     */
    public function markRead(Conversation $conversation, ChatSenderRole $viewer): Conversation
    {
        $otherRole = $viewer === ChatSenderRole::Customer ? ChatSenderRole::Staff : ChatSenderRole::Customer;
        $counter = $viewer === ChatSenderRole::Customer ? 'customer_unread' : 'admin_unread';

        $changed = $conversation->messages()
            ->where('sender_role', $otherRole)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($changed > 0 || $conversation->{$counter} > 0) {
            $conversation->forceFill([$counter => 0])->save();
            $conversation->load(['customer', 'assignee']);
            $this->broadcast(new ConversationUpdated($this->serializeConversation($conversation), 'read'));
        }

        return $conversation;
    }

    public function setStatus(Conversation $conversation, ConversationStatus $status): Conversation
    {
        if ($conversation->status !== $status) {
            $conversation->update([
                'status' => $status,
                'closed_at' => $status === ConversationStatus::Closed ? now() : null,
            ]);
            $conversation->load(['customer', 'assignee']);
            $this->broadcast(new ConversationUpdated($this->serializeConversation($conversation), 'status'));
        }

        return $conversation;
    }

    public function assign(Conversation $conversation, ?User $staff): Conversation
    {
        $conversation->update(['assigned_to' => $staff?->id]);
        $conversation->load(['customer', 'assignee']);
        $this->broadcast(new ConversationUpdated($this->serializeConversation($conversation), 'assigned'));

        return $conversation;
    }

    /**
     * Admin inbox list.
     *
     * @param  array<string, mixed>  $filters  search, status, assigned (int|'me'), unread (bool)
     */
    public function paginateInbox(array $filters, int $perPage, ?User $viewer = null): LengthAwarePaginator
    {
        $assigned = $filters['assigned'] ?? null;
        if ($assigned === 'me') {
            $assigned = $viewer?->id;
        }

        return Conversation::query()
            ->with(['customer', 'assignee'])
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->assignedTo($assigned ? (int) $assigned : null)
            ->when(! empty($filters['unread']), fn ($q) => $q->unread())
            ->orderByRaw('CASE WHEN last_message_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Inbox counters for the header / filter chips.
     *
     * @return array{open:int, unread:int, closed:int, mine:int}
     */
    public function inboxStats(?User $viewer = null): array
    {
        return [
            'open' => Conversation::query()->open()->count(),
            'unread' => Conversation::query()->unread()->count(),
            'closed' => Conversation::query()->where('status', ConversationStatus::Closed)->count(),
            'mine' => $viewer ? Conversation::query()->open()->where('assigned_to', $viewer->id)->count() : 0,
        ];
    }

    /**
     * Total unread customer messages across all threads (admin header badge).
     */
    public function unreadForAdmin(): int
    {
        return (int) Conversation::query()->sum('admin_unread');
    }

    public function unreadForCustomer(User $customer): int
    {
        return (int) Conversation::query()->where('user_id', $customer->id)->value('customer_unread');
    }

    /**
     * Staff members who can be assigned a thread.
     */
    public function assignableStaff(): Collection
    {
        return User::query()
            ->permission('view chats')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);
    }

    /**
     * A page of messages. Without a cursor: the latest PAGE_SIZE. With before_id:
     * older ones. With after_id: newer ones (polling fallback). Always oldest→newest.
     *
     * @return array{messages: array<int, array<string, mixed>>, has_more: bool}
     */
    public function messagesPage(Conversation $conversation, ?int $beforeId = null, ?int $afterId = null, int $limit = self::PAGE_SIZE): array
    {
        $query = $conversation->messages()->with(['sender', 'product']);

        if ($afterId) {
            $rows = $query->where('id', '>', $afterId)->orderBy('id')->limit($limit + 1)->get();
            $hasMore = $rows->count() > $limit;
            $rows = $rows->take($limit);
        } else {
            $rows = $query->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
                ->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $rows->count() > $limit;
            $rows = $rows->take($limit)->reverse()->values();
        }

        return [
            'messages' => $rows->map(fn (ChatMessage $m) => $this->serializeMessage($m))->values()->all(),
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(ChatMessage $message): array
    {
        $sender = $message->relationLoaded('sender') ? $message->sender : null;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'sender_role' => $message->sender_role->value,
            'sender' => [
                'id' => $message->sender_id,
                'name' => $sender?->name ?? '',
                'avatar' => $sender?->avatarUrl(),
            ],
            'product' => $message->relationLoaded('product') && $message->product
                ? $this->serializeProduct($message->product)
                : null,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * Compact product snapshot attached to "Ask about this product" messages.
     *
     * @return array<string, mixed>
     */
    public function serializeProduct(Product $product): array
    {
        $slug = $product->slug ?: Str::slug((string) $product->name);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->thumbnail_url,
            'price' => (float) $product->final_price,
            'price_label' => dprice((float) $product->final_price),
            'url' => route('frontend.shop.show', $slug),
            'admin_url' => route('admin.products.edit', $product->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeConversation(Conversation $conversation): array
    {
        $customer = $conversation->relationLoaded('customer') ? $conversation->customer : null;
        $assignee = $conversation->relationLoaded('assignee') ? $conversation->assignee : null;

        return [
            'id' => $conversation->id,
            'status' => $conversation->status->value,
            'subject' => $conversation->subject,
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'customer_unread' => (int) $conversation->customer_unread,
            'admin_unread' => (int) $conversation->admin_unread,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'avatar' => $customer->avatarUrl(),
            ] : null,
            'assignee' => $assignee ? [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'avatar' => $assignee->avatarUrl(),
            ] : null,
        ];
    }

    private function broadcast(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            Log::warning('Chat broadcast failed: '.$e->getMessage(), ['event' => $event::class]);
        }
    }
}
