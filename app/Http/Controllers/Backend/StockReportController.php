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

    public function index(): View
    {
        return view('admin.reports.stock', $this->reports->report());
    }

    public function export(Request $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows(),
            'Stock Report',
            'stock-report',
            (string) $request->query('format', 'csv'),
        );
    }
}
