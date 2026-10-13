<?php

use App\Models\Currency;
use App\Models\User;
use App\Services\Admin\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

function khr(array $overrides = []): Currency
{
    return Currency::create($overrides + [
        'code' => 'KHR', 'name' => 'Cambodian Riel', 'symbol' => '៛', 'symbol_position' => 'after',
        'exchange_rate' => 4000, 'decimals' => 0, 'status' => true, 'sort_order' => 1,
    ]);
}

it('shows only the base currency when no extra currency is enabled', function () {
    khr(['status' => false]);

    expect(app(SettingService::class)->displayCurrencies())->toHaveCount(1)
        ->and(dprice(10))->toBe(money(10));
});

it('lists every enabled currency after the base, in sort order', function () {
    khr(['sort_order' => 2]);
    Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'exchange_rate' => 0.92, 'decimals' => 2, 'status' => true, 'sort_order' => 1]);
    Currency::create(['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'symbol_position' => 'before', 'exchange_rate' => 36, 'decimals' => 2, 'status' => false]);

    expect(array_keys(app(SettingService::class)->displayCurrencies()))->toBe(['USD', 'EUR', 'KHR']);
});

it('converts browsing prices to the selected currency, never the base money()', function () {
    khr();
    Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'exchange_rate' => 0.5, 'decimals' => 2, 'status' => true]);

    expect(dprice(10))->toBe(money(10)); // no session → base

    session(['display_currency' => 'KHR']);
    expect(dprice(10))->toBe('40,000៛')->and(money(10))->toBe('$10.00');

    session(['display_currency' => 'EUR']);
    expect(dprice(10))->toBe('€5.00');
});

it('ignores an unknown or disabled currency and falls back to base', function () {
    khr(['status' => false]);

    session(['display_currency' => 'KHR']);
    expect(dprice(10))->toBe(money(10));

    session(['display_currency' => 'XYZ']);
    expect(dprice(10))->toBe(money(10));
});

it('drops the cached list when a currency changes', function () {
    $c = khr();
    expect(app(SettingService::class)->displayCurrencies())->toHaveKey('KHR');

    $c->update(['status' => false]);
    expect(app(SettingService::class)->displayCurrencies())->not->toHaveKey('KHR');
});

it('only switches to an enabled currency', function () {
    khr();

    $this->from(route('frontend.home'))->get(route('currency.switch', 'KHR'))->assertRedirect(route('frontend.home'));
    expect(session('display_currency'))->toBe('KHR');

    $this->get(route('currency.switch', 'ZZZ'));
    expect(session('display_currency'))->toBe('KHR');
});

it('shows the switcher with every enabled currency on the storefront', function () {
    khr();

    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee(route('currency.switch', 'KHR'), false)
        ->assertSee('Cambodian Riel');
});

describe('admin', function () {
    beforeEach(function () {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    });

    it('lists, creates, updates and deletes currencies', function () {
        $this->actingAs($this->admin)->get(route('admin.currencies.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.currencies.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.currencies.store'), [
            'code' => 'khr', 'name' => 'Cambodian Riel', 'symbol' => '៛', 'symbol_position' => 'after',
            'exchange_rate' => '4100', 'decimals' => '0', 'status' => '1', 'sort_order' => '0',
        ])->assertRedirect(route('admin.currencies.index'))->assertSessionHasNoErrors();

        $c = Currency::firstWhere('code', 'KHR'); // normalised to upper case
        expect($c)->not->toBeNull()->and((float) $c->exchange_rate)->toBe(4100.0);

        $this->actingAs($this->admin)->get(route('admin.currencies.edit', $c->id))->assertOk()->assertSee('4100');

        $this->actingAs($this->admin)->put(route('admin.currencies.update', $c->id), [
            'code' => 'KHR', 'name' => 'Riel', 'symbol' => '៛', 'symbol_position' => 'after',
            'exchange_rate' => '4050', 'decimals' => '0', 'status' => '1',
        ])->assertRedirect(route('admin.currencies.index'))->assertSessionHasNoErrors();
        expect((float) $c->fresh()->exchange_rate)->toBe(4050.0);

        $this->actingAs($this->admin)->delete(route('admin.currencies.destroy', $c->id))->assertRedirect(route('admin.currencies.index'));
        expect(Currency::count())->toBe(0);
    });

    it('rejects the base currency, duplicates, bad codes and non-positive rates', function () {
        khr();
        $valid = ['name' => 'X', 'symbol' => 'x', 'symbol_position' => 'before', 'exchange_rate' => '1', 'decimals' => '2', 'status' => '1'];

        foreach (['USD', 'KHR', 'EURO', '12'] as $code) {
            $this->actingAs($this->admin)->post(route('admin.currencies.store'), ['code' => $code] + $valid)->assertSessionHasErrors('code');
        }

        $this->actingAs($this->admin)->post(route('admin.currencies.store'), ['code' => 'EUR', 'exchange_rate' => '0'] + $valid)
            ->assertSessionHasErrors('exchange_rate');
    });

    it('bulk-disables currencies and the storefront list follows', function () {
        $c = khr();

        $this->actingAs($this->admin)->patch(route('admin.currencies.bulk-status'), ['ids' => [$c->id], 'status' => '0'])->assertRedirect();

        expect(app(SettingService::class)->displayCurrencies())->not->toHaveKey('KHR');
    });

    it('forbids staff without the currencies permission', function () {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('admin.currencies.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.currencies.store'), [
            'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before',
            'exchange_rate' => '0.9', 'decimals' => '2', 'status' => '1',
        ])->assertForbidden();
        expect(Currency::count())->toBe(0);
    });
});
