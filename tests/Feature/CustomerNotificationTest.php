<?php

use App\Mail\CustomerBroadcastMail;
use App\Models\CustomerNotificationCampaign;
use App\Models\CustomerProfile;
use App\Models\CustomerTag;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->unprivileged = User::factory()->create();
    $this->unprivileged->assignRole('customer');

    Mail::fake();
});

function customerOrder(string $email, ?string $name = null): Order
{
    return Order::create([
        'user_id' => null,
        'status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'payment_status' => 'paid',
        'customer_name' => $name ?: 'Test Customer',
        'customer_email' => $email,
        'shipping_address' => 'x',
        'subtotal' => 10,
        'discount_total' => 0,
        'shipping_total' => 0,
        'tax_total' => 0,
        'grand_total' => 10,
        'placed_at' => now(),
    ]);
}

// -- Authorization -------------------------------------------------------------------

it('denies customer notifications to a user without the permission', function () {
    $this->actingAs($this->unprivileged)
        ->get(route('admin.customer-notifications.index'))
        ->assertForbidden();

    $this->actingAs($this->unprivileged)
        ->get(route('admin.customer-notifications.create'))
        ->assertForbidden();

    $this->actingAs($this->unprivileged)
        ->post(route('admin.customer-notifications.store'), [
            'title' => 'x', 'message' => 'x', 'audience_type' => 'all',
        ])
        ->assertForbidden();
});

it('allows an admin to view the notifications pages', function () {
    $this->actingAs($this->admin)->get(route('admin.customer-notifications.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.customer-notifications.create'))->assertOk();
});

// -- Sending to all customers ---------------------------------------------------------

it('sends a bulk notification by email to every customer and logs a campaign', function () {
    customerOrder('guest@example.com');
    $registered = User::factory()->create(['email' => 'registered@example.com']);
    customerOrder('registered@example.com');

    $this->actingAs($this->admin)
        ->post(route('admin.customer-notifications.store'), [
            'title' => 'Big Sale',
            'message' => 'Everything is 20% off this weekend.',
            'audience_type' => 'all',
        ])
        ->assertRedirect(route('admin.customer-notifications.index'));

    Mail::assertQueued(CustomerBroadcastMail::class, fn ($mail) => $mail->hasTo('guest@example.com'));
    Mail::assertQueued(CustomerBroadcastMail::class, fn ($mail) => $mail->hasTo('registered@example.com'));

    $campaign = CustomerNotificationCampaign::first();
    expect($campaign->title)->toBe('Big Sale')
        ->and($campaign->recipient_count)->toBe(2)
        ->and($campaign->registered_recipient_count)->toBe(1)
        ->and($campaign->audience_summary)->toBe('All customers');

    // The registered customer gets an in-app inbox entry too.
    $notification = $registered->fresh()->notifications()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Big Sale')
        ->and($notification->data['type'])->toBe('promo');
});

it('does not create an in-app notification for a guest-only customer', function () {
    customerOrder('guestonly@example.com');

    $this->actingAs($this->admin)->post(route('admin.customer-notifications.store'), [
        'title' => 'Hello', 'message' => 'Hi there.', 'audience_type' => 'all',
    ]);

    $campaign = CustomerNotificationCampaign::first();
    expect($campaign->registered_recipient_count)->toBe(0);
});

// -- Segment targeting -----------------------------------------------------------------

it('only notifies customers matching the selected tag segment', function () {
    customerOrder('vip@example.com', 'VIP Customer');
    customerOrder('regular@example.com', 'Regular Customer');

    // Migration 2026_07_17_000002 seeds a default 'VIP' tag — reuse it.
    $tag = CustomerTag::firstOrCreate(['name' => 'VIP']);
    $profile = CustomerProfile::firstOrCreate(['email' => 'vip@example.com'], ['name' => 'VIP Customer']);
    $profile->tags()->sync([$tag->id]);

    $this->actingAs($this->admin)->post(route('admin.customer-notifications.store'), [
        'title' => 'VIP Perk',
        'message' => 'Exclusive early access.',
        'audience_type' => 'segment',
        'tag_id' => $tag->id,
    ]);

    Mail::assertQueued(CustomerBroadcastMail::class, fn ($mail) => $mail->hasTo('vip@example.com'));
    Mail::assertNotQueued(CustomerBroadcastMail::class, fn ($mail) => $mail->hasTo('regular@example.com'));

    $campaign = CustomerNotificationCampaign::first();
    expect($campaign->recipient_count)->toBe(1)
        ->and($campaign->audience_summary)->toContain('VIP');
});

// -- Live recipient-count preview -------------------------------------------------------

it('previews the recipient count for the current audience selection', function () {
    customerOrder('a@example.com');
    customerOrder('b@example.com');

    $this->actingAs($this->admin)
        ->getJson(route('admin.customer-notifications.preview-count', ['audience_type' => 'all']))
        ->assertOk()
        ->assertJson(['count' => 2]);
});
