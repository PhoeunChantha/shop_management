<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Services\AbandonedCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbandonedCartController extends Controller
{
    public function __construct(private readonly AbandonedCartService $carts) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view abandoned carts'), 403);

        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.abandoned-carts.index', [
            'carts' => $this->carts->paginate($filters, $perPage),
            'stats' => $this->carts->stats(),
            'perPage' => $perPage,
        ]);
    }

    public function show(Request $request, AbandonedCart $cart): View
    {
        abort_unless($request->user()->hasPermissionTo('view abandoned carts'), 403);

        return view('admin.abandoned-carts.show', [
            'cart' => $this->carts->findForShow($cart),
        ]);
    }

    public function update(Request $request, AbandonedCart $cart): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('edit abandoned carts'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(AbandonedCart::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->carts->updateWorkflow($cart, $data);

        return back()->with('success', 'Abandoned cart workflow updated.');
    }

    public function destroy(Request $request, AbandonedCart $cart): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('delete abandoned carts'), 403);

        $this->carts->delete($cart);

        return to_route('admin.abandoned-carts.index')->with('success', 'Abandoned cart deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermissionTo('view abandoned carts'), 403);

        $filters = $this->validatedFilters($request);
        $filename = 'abandoned-carts-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            $this->carts->writeCsv($filters, $handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(AbandonedCart::STATUSES))],
            'age' => ['nullable', Rule::in(array_keys(AbandonedCartService::AGE_FILTERS))],
            'value' => ['nullable', Rule::in(array_keys(AbandonedCartService::VALUE_FILTERS))],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }
}
