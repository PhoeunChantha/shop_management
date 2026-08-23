<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Services\Admin\FinanceReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class FinanceReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly FinanceReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.reports.finance', array_merge($this->reports->overview($filters), [
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
        ]));
    }

    public function export(string $type, Request $request): Response
    {
        abort_unless(in_array($type, ['sales', 'products', 'customers', 'purchases'], true), 404);

        return $this->streamExport(
            $this->reports->exportRows($type, $this->validatedFilters($request)),
            'Overview — '.ucfirst($type),
            'finance-'.$type.'-report',
            (string) $request->query('format', 'csv'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);
    }
}
