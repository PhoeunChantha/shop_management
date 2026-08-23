<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Enums\ChatSenderRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Services\Admin\ChatService;
use App\Services\Frontend\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer side of live chat. Every action works on the signed-in customer's own
 * conversation (resolved server-side — no id in the URL), so ownership is implicit.
 */
class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly AccountService $account,
    ) {}

    /**
     * Account → Messages page (full-size thread).
     */
    public function index(Request $request): View
    {
        $conversation = $this->chat->conversationFor($request->user());

        return view('frontend.account.messages', [
            'user' => $this->account->user(),
            'conversation' => $conversation,
        ]);
    }

    /**
     * Message feed: latest page, older page (before_id) or newer page (after_id).
     */
    public function feed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
            'after_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $conversation = $this->chat->conversationFor($request->user());
        $page = $this->chat->messagesPage(
            $conversation,
            isset($data['before_id']) ? (int) $data['before_id'] : null,
            isset($data['after_id']) ? (int) $data['after_id'] : null,
        );

        $conversation->load(['customer', 'assignee']);

        return response()->json($page + [
            'conversation' => $this->chat->serializeConversation($conversation),
        ]);
    }

    public function store(StoreChatMessageRequest $request): JsonResponse
    {
        $conversation = $this->chat->conversationFor($request->user());
        $message = $this->chat->sendMessage(
            $conversation,
            $request->user(),
            $request->validated('body'),
            ChatSenderRole::Customer,
            $request->validated('product_id') ? (int) $request->validated('product_id') : null,
        );

        return response()->json([
            'message' => $this->chat->serializeMessage($message),
            'conversation' => $this->chat->serializeConversation($conversation),
        ], 201);
    }

    public function read(Request $request): JsonResponse
    {
        $conversation = $this->chat->markRead(
            $this->chat->conversationFor($request->user()),
            ChatSenderRole::Customer,
        );

        return response()->json(['unread' => (int) $conversation->customer_unread]);
    }

    /**
     * Lightweight state poll (used when websockets are unavailable).
     */
    public function state(Request $request): JsonResponse
    {
        $conversation = $this->chat->conversationFor($request->user());
        $conversation->load(['customer', 'assignee']);

        return response()->json([
            'conversation' => $this->chat->serializeConversation($conversation),
            'last_message_id' => (int) ($conversation->messages()->max('id') ?? 0),
        ]);
    }
}
