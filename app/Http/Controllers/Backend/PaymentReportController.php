<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Services\Admin\PaymentReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PaymentReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly PaymentReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.reports.payments', array_merge($this->reports->report($filters), [
            'perPage' => (int) ($filters['per_page'] ?? 25),
            'txStatuses' => [
                'completed' => __('Captured'),
                'pending' => __('Pending'),
                'failed' => __('Failed'),
            ],
        ]));
    }

    public function export(Request $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows($this->validatedFilters($request)),
            'Payment Report',
            'payment-report',
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
            'status' => ['nullable', Rule::in(['completed', 'pending', 'failed'])],
            'method' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);
    }
}
