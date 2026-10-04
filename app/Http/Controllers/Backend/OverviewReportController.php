<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\OverviewReportRequest;
use App\Services\Admin\OverviewReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class OverviewReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly OverviewReportService $reports,
    ) {}

    public function index(OverviewReportRequest $request): View
    {
        return view('admin.reports.overview', [
            ...$this->reports->report($request->filters(), $request->user()->can('view finance reports')),
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
        ]);
    }

    public function export(OverviewReportRequest $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows($request->filters()),
            'Overview — Sales by period',
            'overview-sales',
            $request->exportFormat(),
        );
    }
}
