<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\Admin\BulkActionService;
use App\Services\Admin\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Extra display currencies for the storefront switcher. Browsing only — orders
 * are charged in the base currency (Settings → Localization).
 */
class CurrencyController extends Controller
{
    use HandlesBulkActions;

    public function __construct(private readonly SettingService $settings) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Currency::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $currencies = Currency::query()
            ->search(trim($filters['search'] ?? ''))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.currencies.index', [
            'currencies' => $currencies,
            'perPage' => $perPage,
            'base' => $this->settings->currency(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Currency::class);

        return view('admin.currencies.create', ['base' => $this->settings->currency()]);
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $this->authorize('create', Currency::class);

        $currency = Currency::create($request->validated());

        return to_route('admin.currencies.index')->with('success', __('Currency :code added.', ['code' => $currency->code]));
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Currency::class);

        return view('admin.currencies.edit', [
            'currency' => Currency::findOrFail($id),
            'base' => $this->settings->currency(),
        ]);
    }

    public function update(UpdateCurrencyRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Currency::class);

        $currency = Currency::findOrFail($id);
        $currency->update($request->validated());

        return to_route('admin.currencies.index')->with('success', __('Currency :code updated.', ['code' => $currency->code]));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Currency::class);

        $currency = Currency::findOrFail($id);
        $currency->delete();

        return to_route('admin.currencies.index')->with('success', __('Currency :code deleted.', ['code' => $currency->code]));
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Currency::class);

        $result = $bulk->destroy(Currency::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'currency', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Currency::class);

        [$ids, $status] = $this->validatedStatus($request);
        // setStatus() is a single query (no model events): drop the cached list.
        $count = $bulk->setStatus(Currency::class, $ids, $status);
        Currency::flushCache();

        return back()->with('success', $count.' currency(ies) '.($status ? 'enabled' : 'disabled').'.');
    }
}
