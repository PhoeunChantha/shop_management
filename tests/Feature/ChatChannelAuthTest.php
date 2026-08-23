<?php

use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app');
    // Channels were registered at boot on the test 'null' driver; re-register them on reverb.
    require base_path('routes/channels.php');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->other = User::factory()->create();
    $this->other->assignRole('customer');

    $this->conversation = Conversation::factory()->create(['user_id' => $this->customer->id]);
});

function authorizeChannel($test, User $user, string $channel)
{
    return $test->actingAs($user)->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => $channel,
    ]);
}

it('lets the owning customer subscribe to their thread', function () {
    authorizeChannel($this, $this->customer, 'private-chat.conversation.'.$this->conversation->id)->assertOk();
});

it('lets staff subscribe to any thread and to the admin feed', function () {
    authorizeChannel($this, $this->admin, 'private-chat.conversation.'.$this->conversation->id)->assertOk();
    authorizeChannel($this, $this->admin, 'private-admin.chat')->assertOk();
});

it('blocks other customers from a thread and from the admin feed', function () {
    authorizeChannel($this, $this->other, 'private-chat.conversation.'.$this->conversation->id)->assertForbidden();
    authorizeChannel($this, $this->other, 'private-admin.chat')->assertForbidden();
});

it('blocks subscriptions to unknown threads', function () {
    authorizeChannel($this, $this->admin, 'private-chat.conversation.999999')->assertForbidden();
});
