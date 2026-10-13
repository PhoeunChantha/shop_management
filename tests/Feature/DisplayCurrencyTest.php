<?php

use App\Models\Currency;
use App\Models\User;
use App\Services\Admin\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
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

describe('settings tab', function () {
    beforeEach(function () {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    });

    function currencyRow(array $overrides = []): array
    {
        return $overrides + ['code' => 'KHR', 'name' => 'Cambodian Riel', 'symbol' => '៛', 'symbol_position' => 'after', 'exchange_rate' => '4100', 'decimals' => '0', 'status' => '1'];
    }

    it('shows the Currencies tab with the saved rows and no separate page', function () {
        khr();

        $this->actingAs($this->admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('data-tab="currencies"', false)
            ->assertSee('Cambodian Riel');

        expect(Route::has('admin.currencies.index'))->toBeFalse();
    });

    it('adds, updates, reorders and removes currencies on save', function () {
        $save = fn (array $rows) => $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
            'currency_code' => 'USD', 'currencies_submitted' => '1', 'currencies' => $rows,
        ]);

        $save([currencyRow(['code' => 'khr']), currencyRow(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'exchange_rate' => '0.92', 'decimals' => '2'])])
            ->assertOk();

        expect(Currency::orderBy('sort_order')->pluck('code')->all())->toBe(['KHR', 'EUR'])
            ->and(array_keys(app(SettingService::class)->displayCurrencies()))->toBe(['USD', 'KHR', 'EUR']);

        // Same codes again (an AJAX save without reload): updated, not duplicated.
        $save([currencyRow(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.9', 'decimals' => '2']), currencyRow(['exchange_rate' => '4050', 'status' => '0'])])
            ->assertOk();

        expect(Currency::count())->toBe(2)
            ->and(Currency::orderBy('sort_order')->pluck('code')->all())->toBe(['EUR', 'KHR'])
            ->and((float) Currency::firstWhere('code', 'KHR')->exchange_rate)->toBe(4050.0)
            ->and(app(SettingService::class)->displayCurrencies())->not->toHaveKey('KHR'); // disabled

        // Removing a row deletes it; a blank row is ignored.
        $save([currencyRow(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.9']), ['code' => '', 'name' => '', 'symbol' => '', 'exchange_rate' => '']])
            ->assertOk();

        expect(Currency::pluck('code')->all())->toBe(['EUR']);
    });

    it('leaves currencies alone when the tab was not part of the save', function () {
        khr();

        $this->actingAs($this->admin)->putJson(route('admin.settings.update'), ['currency_code' => 'USD'])->assertOk();

        expect(Currency::count())->toBe(1);
    });

    it('rejects bad rows', function (array $row, string $field) {
        $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
            'currency_code' => 'USD', 'currencies_submitted' => '1', 'currencies' => [$row],
        ])->assertStatus(422)->assertJsonValidationErrors("currencies.0.{$field}");

        expect(Currency::count())->toBe(0);
    })->with([
        'base currency' => [currencyRow(['code' => 'usd']), 'code'],
        'not 3 letters' => [currencyRow(['code' => 'EURO']), 'code'],
        'no rate' => [currencyRow(['exchange_rate' => '']), 'exchange_rate'],
        'zero rate' => [currencyRow(['exchange_rate' => '0']), 'exchange_rate'],
        'no symbol' => [currencyRow(['symbol' => '']), 'symbol'],
    ]);

    it('rejects the same currency twice', function () {
        $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
            'currency_code' => 'USD', 'currencies_submitted' => '1', 'currencies' => [currencyRow(), currencyRow(['code' => 'khr'])],
        ])->assertStatus(422)->assertJsonValidationErrors('currencies.1.code');
    });

    it('checks the base currency being saved in the same submit', function () {
        // Switching the base to KHR while KHR is still listed as an extra currency.
        $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
            'currency_code' => 'KHR', 'currencies_submitted' => '1', 'currencies' => [currencyRow()],
        ])->assertStatus(422)->assertJsonValidationErrors('currencies.0.code');
    });

    it('forbids staff without the settings permission', function () {
        $this->actingAs(User::factory()->create())->putJson(route('admin.settings.update'), [
            'currency_code' => 'USD', 'currencies_submitted' => '1', 'currencies' => [currencyRow()],
        ])->assertForbidden();

        expect(Currency::count())->toBe(0);
    });
});
