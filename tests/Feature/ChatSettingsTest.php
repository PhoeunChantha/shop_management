<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

it('shows the Live Chat tab on the settings page and deep-links to it', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.index', ['tab' => 'chat']))
        ->assertOk()
        ->assertSee('Live Chat')
        ->assertSee('chat_sound_admin', false)
        ->assertSee("x-data=\"{ tab: 'chat' }\"", false);
});

it('saves chat settings and exposes them through SettingService::chat()', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'chat_enabled' => '1',
            'chat_guest_launcher' => '0',
            'chat_ask_product_enabled' => '0',
            'chat_header_title' => 'Concierge desk',
            'chat_reply_note' => 'Back within the hour',
            'chat_welcome_title' => 'Hello there',
            'chat_welcome_text' => 'Ask us anything.',
            'chat_product_prefill' => 'Question about this item:',
            'chat_sound_admin' => 'ding',
            'chat_sound_customer' => 'off',
            'chat_sound_volume' => '50',
        ])
        ->assertRedirect(route('admin.settings.index'));

    $chat = app(SettingService::class)->chat();

    expect($chat['enabled'])->toBeTrue()
        ->and($chat['guest_launcher'])->toBeFalse()
        ->and($chat['ask_product'])->toBeFalse()
        ->and($chat['header_title'])->toBe('Concierge desk')
        ->and($chat['reply_note'])->toBe('Back within the hour')
        ->and($chat['welcome_title'])->toBe('Hello there')
        ->and($chat['product_prefill'])->toBe('Question about this item:')
        ->and($chat['sound_admin'])->toBe('ding')
        ->and($chat['sound_customer'])->toBe('off')
        ->and($chat['volume'])->toBe(50);
});

it('rejects an unknown alert sound', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.settings.update'), ['chat_sound_admin' => 'airhorn'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('chat_sound_admin');
});

it('hides the storefront widget and product buttons when chat is disabled', function () {
    Setting::set('chat_enabled', '0', 'chat');

    $this->actingAs($this->customer)->get(route('frontend.home'))
        ->assertOk()
        ->assertDontSee('data-chat-launcher', false)
        ->assertDontSee('data-chat-product=', false);

    // Guests lose the sign-in launcher too.
    $this->get(route('frontend.home'))->assertOk()->assertDontSee('data-chat-launcher', false);
});

it('hides only the guest launcher when that option is off', function () {
    Setting::set('chat_guest_launcher', '0', 'chat');

    $this->get(route('frontend.home'))->assertOk()->assertDontSee('data-chat-launcher', false);
    $this->actingAs($this->customer)->get(route('frontend.home'))->assertOk()->assertSee('data-chat-launcher', false);
});

it('passes the customer alert sound into the storefront config', function () {
    Setting::set('chat_sound_customer', 'chime', 'chat');
    Setting::set('chat_sound_volume', '25', 'chat');

    $this->actingAs($this->customer)->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('sound: { kind: "chime", volume: 25 }', false);
});

it('renders the admin sound meta tags for staff who can view chats', function () {
    Setting::set('chat_sound_admin', 'pop', 'chat');

    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<meta name="admin-chat-sound" content="pop">', false);
});
