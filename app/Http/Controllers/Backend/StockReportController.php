<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Services\Admin\StockReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class StockReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly StockReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        return view('admin.reports.stock', array_merge($this->reports->report($filters), [
            'perPage' => (int) ($filters['per_page'] ?? 25),
        ]));
    }

    public function export(Request $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows(),
            'Inventory Report',
            'inventory-report',
            (string) $request->query('format', 'csv'),
        );
    }
}
