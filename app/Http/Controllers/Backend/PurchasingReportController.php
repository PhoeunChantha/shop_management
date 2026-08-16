<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Services\Admin\PurchasingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PurchasingReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly PurchasingReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.reports.purchasing', $this->reports->report($this->validatedFilters($request)));
    }

    public function export(Request $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows($this->validatedFilters($request)),
            'Purchasing Report',
            'purchasing-report',
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
        ]);
    }
}
