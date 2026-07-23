<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Services\FinanceReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FinanceReportController extends Controller
{
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

    public function export(string $type, Request $request): StreamedResponse
    {
        abort_unless(in_array($type, ['sales', 'products', 'customers', 'purchases'], true), 404);

        $rows = $this->reports->exportRows($type, $this->validatedFilters($request));

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'finance-'.$type.'-report-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
