<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\ChatSenderRole;
use App\Enums\ConversationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Admin\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin live-chat inbox. Page load renders the conversation list; the thread pane
 * is driven by the JSON actions below and Reverb pushes.
 */
class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(ConversationStatus::options()))],
            'assigned' => ['nullable', 'string', 'max:10'],
            'unread' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'conversation' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);
        $conversations = $this->chat->paginateInbox($filters, $perPage, $request->user());

        $selected = null;
        if (! empty($filters['conversation'])) {
            $selected = Conversation::query()->with(['customer', 'assignee'])->find((int) $filters['conversation']);
        }

        return view('admin.chats.index', [
            'conversations' => $conversations,
            'perPage' => $perPage,
            'filters' => $filters,
            'stats' => $this->chat->inboxStats($request->user()),
            'staff' => $this->chat->assignableStaff(),
            'selected' => $selected ? $this->chat->serializeConversation($selected) : null,
            'statuses' => ConversationStatus::options(),
        ]);
    }

    /**
     * Thread payload: conversation summary + latest page of messages.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', Conversation::class);

        $conversation->load(['customer', 'assignee']);
        $page = $this->chat->messagesPage($conversation);

        return response()->json($page + [
            'conversation' => $this->chat->serializeConversation($conversation),
        ]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', Conversation::class);

        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
            'after_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($this->chat->messagesPage(
            $conversation,
            isset($data['before_id']) ? (int) $data['before_id'] : null,
            isset($data['after_id']) ? (int) $data['after_id'] : null,
        ));
    }

    public function store(StoreChatMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', Conversation::class);

        $message = $this->chat->sendMessage(
            $conversation,
            $request->user(),
            $request->validated('body'),
            ChatSenderRole::Staff,
            $request->validated('product_id') ? (int) $request->validated('product_id') : null,
        );

        return response()->json([
            'message' => $this->chat->serializeMessage($message),
            'conversation' => $this->chat->serializeConversation($conversation),
        ], 201);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', Conversation::class);

        $conversation = $this->chat->markRead($conversation, ChatSenderRole::Staff);
        $conversation->load(['customer', 'assignee']);

        return response()->json([
            'conversation' => $this->chat->serializeConversation($conversation),
            'unread_total' => $this->chat->unreadForAdmin(),
        ]);
    }

    public function updateStatus(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', Conversation::class);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ConversationStatus::options()))],
        ]);

        $conversation = $this->chat->setStatus($conversation, ConversationStatus::from($data['status']));

        return response()->json(['conversation' => $this->chat->serializeConversation($conversation)]);
    }

    public function assign(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', Conversation::class);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $staff = ! empty($data['assigned_to']) ? User::query()->find((int) $data['assigned_to']) : null;
        $conversation = $this->chat->assign($conversation, $staff);

        return response()->json(['conversation' => $this->chat->serializeConversation($conversation)]);
    }

    /**
     * Unread total for the header badge (polling fallback).
     */
    public function unread(): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        return response()->json(['unread' => $this->chat->unreadForAdmin()]);
    }
}
