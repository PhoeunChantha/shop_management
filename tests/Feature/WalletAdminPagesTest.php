<?php

use App\Models\User;
use App\Models\WalletTopup;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create(['name' => 'Dara Sok', 'wallet_balance' => 20]);
    $this->customer->assignRole('customer');
});

function topupFor(User $user, string $status, array $extra = []): WalletTopup
{
    return WalletTopup::create(array_merge([
        'user_id' => $user->id,
        'tran_id' => 'WT-'.strtoupper(uniqid()),
        'payment_method' => 'aba_qr',
        'method_type' => 'manual',
        'amount' => 50,
        'status' => $status,
    ], $extra));
}

// ── Wallets page: display-only rows + modal ─────────────────────────────

it('shows customer rows without inline credit/debit inputs and offers the Adjust balance modal', function () {
    $html = $this->actingAs($this->admin)->get(route('admin.wallets.index'))->assertOk()->getContent();
    $table = substr($html, strpos($html, 'Customer Wallets'), 9000);

    expect($html)->toContain('openWalletAdjust()')                       // top button
        ->toContain('openWalletAdjust('.$this->customer->id.')')         // row shortcut
        ->toContain('walletAdjustModal(')
        ->toContain(route('admin.wallets.adjust'))
        ->and($table)->not->toContain('name="amount" min="0.01" step="0.01" placeholder="0.00" required class="wallet-adjust__amount"')
        ->not->toContain('wallet-adjust__credit');
});

it('credits and debits from the modal', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.wallets.adjust'), ['form_mode' => 'wallet-adjust', 'user_id' => $this->customer->id, 'direction' => 'credit', 'amount' => '5.50', 'note' => 'Goodwill'])
        ->assertRedirect()->assertSessionHas('success');
    expect((float) $this->customer->fresh()->wallet_balance)->toBe(25.5);

    $this->actingAs($this->admin)
        ->post(route('admin.wallets.adjust'), ['form_mode' => 'wallet-adjust', 'user_id' => $this->customer->id, 'direction' => 'debit', 'amount' => '10'])
        ->assertRedirect();
    expect((float) $this->customer->fresh()->wallet_balance)->toBe(15.5)
        ->and($this->customer->walletTransactions()->first()->description)->toBe('Admin debit');
});

it('refuses a debit larger than the balance and reopens the modal with the input', function () {
    // Follow the redirect the way a browser does, then inspect the re-rendered page.
    $html = $this->actingAs($this->admin)
        ->from(route('admin.wallets.index'))
        ->followingRedirects()
        ->post(route('admin.wallets.adjust'), ['form_mode' => 'wallet-adjust', 'user_id' => $this->customer->id, 'direction' => 'debit', 'amount' => '99'])
        ->assertOk()
        ->getContent();

    expect((float) $this->customer->fresh()->wallet_balance)->toBe(20.0)
        ->and($html)->toContain('reopen: true')
        ->toContain("amount: '99'")
        ->toContain('This customer only has $20.00 in their wallet.');
});

it('only adjusts customer wallets', function () {
    $staff = User::factory()->create();
    $staff->assignRole('admin');

    $this->actingAs($this->admin)
        ->post(route('admin.wallets.adjust'), ['user_id' => $staff->id, 'direction' => 'credit', 'amount' => 5])
        ->assertSessionHasErrors('user_id');
});

// ── Top-up Requests page ────────────────────────────────────────────────

it('lists pending top-ups on their own page and in the sidebar badge', function () {
    topupFor($this->customer, 'pending', ['tran_id' => 'WT-PENDING-1']);
    topupFor($this->customer, 'completed', ['tran_id' => 'WT-DONE-1', 'reviewed_at' => now(), 'approved_by' => $this->admin->id]);

    $html = $this->actingAs($this->admin)->get(route('admin.wallets.topups.index'))->assertOk()->getContent();

    expect($html)->toContain('WT-PENDING-1')
        ->toContain(route('admin.wallets.topups.approve', WalletTopup::where('tran_id', 'WT-PENDING-1')->value('id')))
        ->not->toContain('WT-DONE-1')
        ->toContain('Top-up Requests')
        ->toContain('class="admin-module__badge"');
});

it('shows approved and rejected requests on the Reviewed tab', function () {
    topupFor($this->customer, 'completed', ['tran_id' => 'WT-OK-1', 'reviewed_at' => now(), 'approved_by' => $this->admin->id]);
    topupFor($this->customer, 'failed', ['tran_id' => 'WT-NO-1', 'reviewed_at' => now(), 'admin_note' => 'No payment received']);
    topupFor($this->customer, 'pending', ['tran_id' => 'WT-WAIT-1']);

    $this->actingAs($this->admin)->get(route('admin.wallets.topups.index', ['tab' => 'reviewed']))
        ->assertOk()
        ->assertSee('WT-OK-1')->assertSee('Approved')
        ->assertSee('WT-NO-1')->assertSee('No payment received')
        ->assertDontSee('WT-WAIT-1');
});

it('no longer shows the pending block on the Wallets page, just a link', function () {
    topupFor($this->customer, 'pending', ['tran_id' => 'WT-PENDING-2']);

    $this->actingAs($this->admin)->get(route('admin.wallets.index'))
        ->assertOk()
        ->assertDontSee('WT-PENDING-2')
        ->assertSee(route('admin.wallets.topups.index'), false);
});

it('hides the top-up page from users without wallet permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.wallets.topups.index'))->assertForbidden();
});

it('shows pending top-ups as a table with approve and a reject popup', function () {
    $topup = topupFor($this->customer, 'pending', ['tran_id' => 'WT-TABLE-1']);

    $html = $this->actingAs($this->admin)->get(route('admin.wallets.topups.index'))->assertOk()->getContent();

    expect($html)->toContain('<table class="premium-table wallet-table">')
        ->toContain('WT-TABLE-1')
        ->toContain(route('admin.wallets.topups.approve', $topup))
        ->toContain('openTopupReject(')
        ->toContain('@topup-reject.window')
        ->not->toContain('wallet-topup-reject-note')   // no reason input in the row
        ->not->toContain('Pending top-up requests');   // duplicate inner heading removed
});
