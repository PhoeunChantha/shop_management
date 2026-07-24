<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinanceReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $orders = $this->ordersBetween($start, $end, $filters);
        $paidStatuses = [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value];
        $paidOrders = (clone $orders)->whereIn('payment_status', $paidStatuses);

        $refunds = ReturnRequest::query()
            ->whereIn('refund_status', ['partial', 'refunded'])
            ->whereBetween(DB::raw('DATE(COALESCE(refunded_at, updated_at))'), [$start->toDateString(), $end->toDateString()])
            ->sum('refund_amount');

        $grossSales = (float) (clone $paidOrders)->sum('grand_total');
        $taxTotal = (float) (clone $paidOrders)->sum('tax_total');
        $shippingTotal = (float) (clone $paidOrders)->sum('shipping_total');
        $discountTotal = (float) (clone $paidOrders)->sum('discount_total');
        $purchaseCost = (float) PurchaseOrder::query()
            ->whereIn('status', ['ordered', 'partial', 'received'])
            ->whereBetween(DB::raw('DATE(COALESCE(ordered_at, created_at))'), [$start->toDateString(), $end->toDateString()])
            ->sum('subtotal');

        return [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $filters['status'] ?? null,
                'payment_status' => $filters['payment_status'] ?? null,
            ],
            'summary' => [
                'gross_sales' => $grossSales,
                'refunds' => (float) $refunds,
                'net_sales' => $grossSales - (float) $refunds,
                'orders' => (clone $orders)->count(),
                'paid_orders' => (clone $paidOrders)->count(),
                'average_order' => (clone $paidOrders)->count() > 0 ? $grossSales / (clone $paidOrders)->count() : 0,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingTotal,
                'discount_total' => $discountTotal,
                'purchase_cost' => $purchaseCost,
            ],
            'salesByDay' => $this->salesByDay($start, $end, $filters),
            'topProducts' => $this->topProducts($start, $end, $filters),
            'customerSpend' => $this->customerSpend($start, $end, $filters),
            'purchaseOrders' => $this->purchaseOrders($start, $end),
            'paymentMix' => $this->paymentMix($start, $end, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(string $type, array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return match ($type) {
            'sales' => $this->salesByDay($start, $end, $filters)
                ->prepend(['Date' => 'Date', 'Orders' => 'Orders', 'Gross Sales' => 'Gross Sales', 'Tax' => 'Tax', 'Shipping' => 'Shipping', 'Discounts' => 'Discounts'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'products' => $this->topProducts($start, $end, $filters)
                ->prepend(['Product' => 'Product', 'SKU' => 'SKU', 'Quantity' => 'Quantity', 'Revenue' => 'Revenue'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'customers' => $this->customerSpend($start, $end, $filters)
                ->prepend(['Customer' => 'Customer', 'Email' => 'Email', 'Orders' => 'Orders', 'Spend' => 'Spend'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'purchases' => $this->purchaseOrders($start, $end)
                ->prepend(['PO Number' => 'PO Number', 'Supplier' => 'Supplier', 'Status' => 'Status', 'Ordered Date' => 'Ordered Date', 'Subtotal' => 'Subtotal'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(array $filters): array
    {
        $end = filled($filters['end_date'] ?? null)
            ? CarbonImmutable::parse((string) $filters['end_date'])->endOfDay()
            : now()->toImmutable()->endOfDay();

        $start = filled($filters['start_date'] ?? null)
            ? CarbonImmutable::parse((string) $filters['start_date'])->startOfDay()
            : $end->subDays(29)->startOfDay();

        if ($start->greaterThan($end)) {
            return [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function ordersBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): Builder
    {
        return Order::query()
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function salesByDay(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as date')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as gross_sales')
            ->selectRaw('SUM(tax_total) as tax')
            ->selectRaw('SUM(shipping_total) as shipping')
            ->selectRaw('SUM(discount_total) as discounts')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (object $row): array => [
                'date' => (string) $row->date,
                'orders' => (int) $row->orders,
                'gross_sales' => (float) $row->gross_sales,
                'tax' => (float) $row->tax,
                'shipping' => (float) $row->shipping,
                'discounts' => (float) $row->discounts,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn ($query) => $query->where('orders.payment_status', $filters['payment_status']))
            ->selectRaw('order_details.name, COALESCE(order_details.sku, "") as sku')
            ->selectRaw('SUM(order_details.quantity) as quantity')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function customerSpend(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->selectRaw('customer_name, customer_email')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as spend')
            ->groupBy('customer_name', 'customer_email')
            ->orderByDesc('spend')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'customer_name' => (string) $row->customer_name,
                'customer_email' => (string) $row->customer_email,
                'orders' => (int) $row->orders,
                'spend' => (float) $row->spend,
            ]);
    }

    /**
     * @return Collection<int, array<string, string|float>>
     */
    private function purchaseOrders(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier:id,name')
            ->whereBetween(DB::raw('DATE(COALESCE(ordered_at, created_at))'), [$start->toDateString(), $end->toDateString()])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'po_number' => $order->po_number,
                'supplier' => $order->supplier?->name ?? 'Unknown supplier',
                'status' => $order->statusLabel(),
                'ordered_at' => $order->ordered_at?->format('Y-m-d') ?? $order->created_at->format('Y-m-d'),
                'subtotal' => (float) $order->subtotal,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int>>
     */
    private function paymentMix(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->orderByDesc('count')
            ->get()
            ->map(function (object $row): array {
                $status = $row->payment_status instanceof PaymentStatus
                    ? $row->payment_status->value
                    : (string) $row->payment_status;

                return [
                    'payment_status' => PaymentStatus::tryFrom($status)?->label() ?? ucfirst($status),
                    'count' => (int) $row->count,
                ];
            });
    }
}
