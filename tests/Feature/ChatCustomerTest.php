<?php

use App\Enums\ChatSenderRole;
use App\Enums\ConversationStatus;
use App\Events\ChatMessageSent;
use App\Events\ConversationUpdated;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

it('redirects guests away from the messages page', function () {
    $this->get(route('frontend.account.messages'))->assertRedirect();
});

it('renders the messages page and creates the conversation on first visit', function () {
    $this->actingAs($this->customer)
        ->get(route('frontend.account.messages'))
        ->assertOk()
        ->assertSee('data-chat-thread', false);

    expect(Conversation::where('user_id', $this->customer->id)->exists())->toBeTrue();
});

it('stores a customer message, bumps admin unread and broadcasts it', function () {
    Event::fake([ChatMessageSent::class]);

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => '  Hello, is my order shipped?  '])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Hello, is my order shipped?')
        ->assertJsonPath('message.sender_role', 'customer')
        ->assertJsonPath('conversation.admin_unread', 1);

    $conversation = Conversation::where('user_id', $this->customer->id)->firstOrFail();
    expect($conversation->admin_unread)->toBe(1)
        ->and($conversation->last_message_preview)->toBe('Hello, is my order shipped?')
        ->and($conversation->messages()->count())->toBe(1);

    Event::assertDispatched(ChatMessageSent::class, fn (ChatMessageSent $e) => $e->conversation['id'] === $conversation->id
        && $e->message['body'] === 'Hello, is my order shipped?');
});

it('rejects an empty or oversized message', function () {
    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => str_repeat('a', 2001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});

it('reopens a closed conversation when the customer writes again', function () {
    $conversation = Conversation::factory()->closed()->create(['user_id' => $this->customer->id]);

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => 'One more thing'])
        ->assertCreated()
        ->assertJsonPath('conversation.status', 'open');

    expect($conversation->refresh()->status)->toBe(ConversationStatus::Open)
        ->and($conversation->closed_at)->toBeNull();
});

it('only feeds the customer their own messages', function () {
    $mine = Conversation::factory()->create(['user_id' => $this->customer->id]);
    ChatMessage::factory()->for($mine)->create(['sender_id' => $this->customer->id, 'body' => 'mine']);

    $other = User::factory()->create();
    $theirs = Conversation::factory()->create(['user_id' => $other->id]);
    ChatMessage::factory()->for($theirs)->create(['sender_id' => $other->id, 'body' => 'secret']);

    $this->actingAs($this->customer)
        ->getJson(route('frontend.account.messages.feed'))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.body', 'mine')
        ->assertJsonPath('conversation.id', $mine->id);
});

it('pages older messages with a before_id cursor', function () {
    $conversation = Conversation::factory()->create(['user_id' => $this->customer->id]);
    for ($i = 1; $i <= 35; $i++) {
        ChatMessage::factory()->for($conversation)->create(['sender_id' => $this->customer->id, 'body' => "m{$i}"]);
    }

    $first = $this->actingAs($this->customer)->getJson(route('frontend.account.messages.feed'))->assertOk();
    $first->assertJsonCount(30, 'messages')->assertJsonPath('has_more', true)->assertJsonPath('messages.29.body', 'm35');

    $oldestId = $first->json('messages.0.id');
    $this->actingAs($this->customer)
        ->getJson(route('frontend.account.messages.feed', ['before_id' => $oldestId]))
        ->assertOk()
        ->assertJsonCount(5, 'messages')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('messages.0.body', 'm1');
});

it('marks staff messages read and clears the customer counter', function () {
    Event::fake([ConversationUpdated::class]);
    $staff = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $this->customer->id, 'customer_unread' => 2]);
    ChatMessage::factory()->for($conversation)->fromStaff()->count(2)->create(['sender_id' => $staff->id]);

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.read'))
        ->assertOk()
        ->assertJsonPath('unread', 0);

    expect($conversation->refresh()->customer_unread)->toBe(0)
        ->and($conversation->messages()->where('sender_role', ChatSenderRole::Staff)->whereNull('read_at')->count())->toBe(0);

    Event::assertDispatched(ConversationUpdated::class, fn ($e) => $e->reason === 'read');
});

it('shows the live chat widget to signed-in customers', function () {
    $this->actingAs($this->customer)->get(route('frontend.home'))->assertOk()
        ->assertSee('data-chat-launcher', false)
        ->assertSee('data-chat-thread', false);
});

it('shows guests only a sign-in launcher, not the thread', function () {
    $this->get(route('frontend.home'))->assertOk()
        ->assertSee('data-chat-launcher', false)
        ->assertDontSee('data-chat-thread', false);
});

it('attaches a product to a message ("Ask about this product")', function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
    $product = Product::factory()->create(['status' => 'active']);

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => 'Does this run small?', 'product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('message.product.id', $product->id)
        ->assertJsonPath('message.product.name', $product->name)
        ->assertJsonPath('message.product.url', route('frontend.shop.show', $product->slug));

    expect(ChatMessage::first()->product_id)->toBe($product->id);

    // The feed carries the product snapshot too.
    $this->actingAs($this->customer)
        ->getJson(route('frontend.account.messages.feed'))
        ->assertOk()
        ->assertJsonPath('messages.0.product.id', $product->id);
});

it('rejects an unknown product id', function () {
    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.messages.store'), ['body' => 'Hi', 'product_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});
