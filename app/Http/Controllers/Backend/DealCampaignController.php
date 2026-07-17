<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\DealCampaign\StoreDealCampaignRequest;
use App\Http\Requests\DealCampaign\UpdateDealCampaignRequest;
use App\Models\DealCampaign;
use App\Services\BulkActionService;
use App\Services\DealCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DealCampaignController extends Controller
{
    use HandlesBulkActions;

    public function __construct(
        private readonly DealCampaignService $deals,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DealCampaign::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys($this->deals->types()))],
            'lifecycle' => ['nullable', Rule::in(['active', 'scheduled', 'expired', 'disabled'])],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.deals.index', [
            'deals' => $this->deals->paginate($filters, $perPage),
            'perPage' => $perPage,
            'types' => $this->deals->types(),
            'stats' => $this->deals->stats(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', DealCampaign::class);

        return view('admin.deals.create', [
            'types' => $this->deals->types(),
            'products' => $this->deals->productOptions(),
            'selected' => old('products', []),
        ]);
    }

    public function store(StoreDealCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', DealCampaign::class);

        $deal = $this->deals->create($request->validated(), $request);

        return to_route('admin.deals.show', $deal)->with('success', 'Deal campaign created successfully.');
    }

    public function show(DealCampaign $deal): View
    {
        $this->authorize('view', $deal);

        return view('admin.deals.show', [
            'deal' => $deal->loadCount('products'),
            'products' => $this->deals->productsForShow($deal),
        ]);
    }

    public function edit(DealCampaign $deal): View
    {
        $this->authorize('update', $deal);

        $deal->load('products:id');

        return view('admin.deals.edit', [
            'deal' => $deal,
            'types' => $this->deals->types(),
            'products' => $this->deals->productOptions(),
            'selected' => old('products', $deal->products->pluck('id')->all()),
        ]);
    }

    public function update(UpdateDealCampaignRequest $request, DealCampaign $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $this->deals->update($deal, $request->validated(), $request);

        return to_route('admin.deals.show', $deal)->with('success', 'Deal campaign updated successfully.');
    }

    public function destroy(DealCampaign $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $this->deals->delete($deal);

        return to_route('admin.deals.index')->with('success', 'Deal campaign deleted successfully.');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', DealCampaign::class);

        $result = $bulk->destroy(DealCampaign::class, $this->validatedIds($request), 'deals');

        return back()->with($this->bulkFlash($result, 'deal campaign', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', DealCampaign::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(DealCampaign::class, $ids, $status);

        return back()->with('success', $count.' deal campaign(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
