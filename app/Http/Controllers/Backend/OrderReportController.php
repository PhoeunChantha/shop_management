<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\OrderReportRequest;
use App\Services\Admin\OrderReportService;
use App\Services\Admin\Reports\PaymentMethodNames;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class OrderReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly OrderReportService $reports,
    ) {}

    public function index(OrderReportRequest $request, PaymentMethodNames $methods): View
    {
        return view('admin.reports.orders', [
            ...$this->reports->report($request->filters(), $request->view()),
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'fulfillmentStatuses' => collect(FulfillmentStatus::cases())->mapWithKeys(fn (FulfillmentStatus $s) => [$s->value => $s->label()])->all(),
            'paymentMethods' => $methods->options(),
        ]);
    }

    public function export(OrderReportRequest $request): Response
    {
        $view = $request->view();

        return $this->streamExport(
            $this->reports->exportRows($request->filters(), $view),
            $view === 'orders' ? 'Orders — List' : 'Orders — Status breakdown',
            'orders-'.$view,
            $request->exportFormat(),
        );
    }
}
