<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\SalesReportRequest;
use App\Services\Admin\Reports\PaymentMethodNames;
use App\Services\Admin\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class SalesReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(SalesReportRequest $request, PaymentMethodNames $methods): View
    {
        return view('admin.reports.sales', [
            ...$this->reports->report($request->filters(), $request->view(), $request->user()->can('view finance reports')),
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'paymentMethods' => $methods->options(),
        ]);
    }

    public function export(SalesReportRequest $request): Response
    {
        $view = $request->view();

        return $this->streamExport(
            $this->reports->exportRows($request->filters(), $view, $request->user()->can('view finance reports')),
            'Sales — '.str_replace('_', ' ', ucfirst($view)),
            'sales-'.$view,
            $request->exportFormat(),
        );
    }

    /** Server-side customer autocomplete for the report filter. */
    public function customers(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        return response()->json([
            'results' => $this->reports->customerOptions($term),
        ]);
    }
}
