<?php

use App\Enums\ConversationStatus;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create(['name' => 'Sok Dara']);
    $this->customer->assignRole('customer');

    $this->conversation = Conversation::factory()->create([
        'user_id' => $this->customer->id,
        'admin_unread' => 1,
        'last_message_preview' => 'Where is my parcel?',
        'last_message_at' => now(),
    ]);
    ChatMessage::factory()->for($this->conversation)->create([
        'sender_id' => $this->customer->id,
        'body' => 'Where is my parcel?',
    ]);
});

it('shows the inbox to staff with view chats permission', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.chats.index'))
        ->assertOk()
        ->assertSee('Sok Dara')
        ->assertSee('Where is my parcel?');
});

it('forbids staff without the permission', function () {
    $this->actingAs($this->customer)->get(route('admin.chats.index'))->assertForbidden();
    $this->actingAs($this->customer)->getJson(route('admin.chats.show', $this->conversation))->assertForbidden();
    $this->actingAs($this->customer)->postJson(route('admin.chats.store', $this->conversation), ['body' => 'hi'])->assertForbidden();
});

it('returns the thread as JSON', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.chats.show', $this->conversation))
        ->assertOk()
        ->assertJsonPath('conversation.id', $this->conversation->id)
        ->assertJsonPath('conversation.customer.name', 'Sok Dara')
        ->assertJsonCount(1, 'messages');
});

it('lets staff reply, bumps the customer counter, auto-assigns and broadcasts', function () {
    Event::fake([ChatMessageSent::class]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.chats.store', $this->conversation), ['body' => 'Shipped this morning!'])
        ->assertCreated()
        ->assertJsonPath('message.sender_role', 'staff')
        ->assertJsonPath('conversation.customer_unread', 1)
        ->assertJsonPath('conversation.assignee.id', $this->admin->id);

    expect($this->conversation->refresh()->customer_unread)->toBe(1)
        ->and($this->conversation->assigned_to)->toBe($this->admin->id);

    Event::assertDispatched(ChatMessageSent::class);
});

it('marks customer messages read and reports the remaining unread total', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.chats.read', $this->conversation))
        ->assertOk()
        ->assertJsonPath('conversation.admin_unread', 0)
        ->assertJsonPath('unread_total', 0);

    expect($this->conversation->refresh()->admin_unread)->toBe(0);
});

it('closes and reopens a conversation', function () {
    $this->actingAs($this->admin)
        ->patchJson(route('admin.chats.status', $this->conversation), ['status' => 'closed'])
        ->assertOk()
        ->assertJsonPath('conversation.status', 'closed');
    expect($this->conversation->refresh()->status)->toBe(ConversationStatus::Closed)
        ->and($this->conversation->closed_at)->not->toBeNull();

    $this->actingAs($this->admin)
        ->patchJson(route('admin.chats.status', $this->conversation), ['status' => 'open'])
        ->assertOk()
        ->assertJsonPath('conversation.status', 'open');
    expect($this->conversation->refresh()->closed_at)->toBeNull();

    $this->actingAs($this->admin)
        ->patchJson(route('admin.chats.status', $this->conversation), ['status' => 'bogus'])
        ->assertUnprocessable();
});

it('assigns and unassigns a staff member', function () {
    $this->actingAs($this->admin)
        ->patchJson(route('admin.chats.assign', $this->conversation), ['assigned_to' => $this->admin->id])
        ->assertOk()
        ->assertJsonPath('conversation.assignee.id', $this->admin->id);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.chats.assign', $this->conversation), ['assigned_to' => null])
        ->assertOk()
        ->assertJsonPath('conversation.assignee', null);
});

it('filters the inbox by status and unread', function () {
    $closed = Conversation::factory()->closed()->create(['last_message_preview' => 'closed thread']);

    $this->actingAs($this->admin)
        ->get(route('admin.chats.index', ['status' => 'closed']))
        ->assertOk()
        ->assertSee('closed thread')
        ->assertDontSee('Where is my parcel?');

    $this->actingAs($this->admin)
        ->get(route('admin.chats.index', ['unread' => 1]))
        ->assertOk()
        ->assertSee('Where is my parcel?')
        ->assertDontSee('closed thread');
});

it('reports the unread total for the header badge', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.chats.unread'))
        ->assertOk()
        ->assertJsonPath('unread', 1);
});
