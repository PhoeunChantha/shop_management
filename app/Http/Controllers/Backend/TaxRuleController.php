<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRule\StoreTaxRuleRequest;
use App\Http\Requests\TaxRule\UpdateTaxRuleRequest;
use App\Models\TaxRule;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TaxRuleController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TaxRule::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $rules = TaxRule::query()
            ->search($search)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.taxes.index', ['rules' => $rules, 'perPage' => $perPage]);
    }

    public function create(): View
    {
        $this->authorize('create', TaxRule::class);

        return view('admin.taxes.create');
    }

    public function store(StoreTaxRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', TaxRule::class);

        try {
            TaxRule::create($request->validated());

            return to_route('admin.taxes.index')->with('success', 'Tax rule created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating tax rule: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the tax rule.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', TaxRule::class);

        return view('admin.taxes.edit', ['rule' => TaxRule::findOrFail($id)]);
    }

    public function update(UpdateTaxRuleRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', TaxRule::class);

        try {
            TaxRule::findOrFail($id)->update($request->validated());

            return to_route('admin.taxes.index')->with('success', 'Tax rule updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating tax rule: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all(), 'id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the tax rule.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', TaxRule::class);

        try {
            TaxRule::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting tax rule: '.$e->getMessage(), ['exception' => $e, 'id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the tax rule.']);
        }

        return to_route('admin.taxes.index')->with('success', 'Tax rule deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', TaxRule::class);

        $result = $bulk->destroy(TaxRule::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'tax rule', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', TaxRule::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(TaxRule::class, $ids, $status);

        return back()->with('success', $count.' tax rule(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
