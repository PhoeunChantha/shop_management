<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Services\Admin\ProductReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class ProductReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly ProductReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.reports.products', array_merge($this->reports->report($filters), [
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'perPage' => (int) ($filters['per_page'] ?? 25),
        ]));
    }

    public function export(Request $request): Response
    {
        $rows = $this->reports->exportRows($this->validatedFilters($request));

        // Cost, profit and margin (columns 4–6) need the finance permission.
        if (! $request->user()->can('view finance reports')) {
            $rows = array_map(fn (array $row) => array_values(array_diff_key($row, array_flip([4, 5, 6]))), $rows);
        }

        return $this->streamExport(
            $rows,
            'Product Report',
            'product-report',
            (string) $request->query('format', 'csv'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:name,sku,quantity,revenue,cogs,profit,margin'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        // Sorting by a cost metric would leak its ranking.
        if (in_array($filters['sort'] ?? null, ['cogs', 'profit', 'margin'], true) && ! $request->user()->can('view finance reports')) {
            unset($filters['sort']);
        }

        return $filters;
    }
}
