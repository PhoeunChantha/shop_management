<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The single definition of "a sale" and "a refund" for every report.
 *
 * ELIGIBLE SALE
 *   An order whose payment was captured: payment_status ∈ {paid,
 *   partially_refunded, refunded}. Dated by placed_at. Unpaid orders —
 *   including cancelled-before-payment and failed PayWay attempts, which leave
 *   the order unpaid — are not sales. Refunded orders stay in gross sales;
 *   their refund is subtracted, never the order removed.
 *
 * REFUNDS (per order, never double-counted)
 *   recorded = Σ return_requests.refund_amount where refund_status ∈
 *              {partial, refunded} — the same set ReturnRequestService uses to
 *              sync the order's payment status — capped at grand_total.
 *   implied  = grand_total − recorded, only when the order is marked refunded
 *              (status = refunded OR payment_status = refunded) — the admin
 *              recorded that the money went back without entering an amount.
 *              A cancelled order whose payment is still "paid" has no
 *              evidence of a refund, so implied = 0.
 *   total    = recorded + implied  (≤ grand_total)
 *
 *   Return refunds are line prices (no tax/shipping), so a refund is applied
 *   to merchandise first (up to subtotal − discount) and any remainder is
 *   tax/shipping given back.
 *
 * WATERFALL
 *   Gross Sales   = Σ subtotal
 *   − Discounts   = Σ discount_total
 *   − Refunds     = merchandise portion of total refunds
 *   = Net Sales
 *   + Tax + Shipping − tax/shipping refunded
 *   = Total Sales (= Σ grand_total − total refunds)
 *
 * Refunds are attributed to the order's placed_at period so one order can
 * never be refunded beyond its own total across periods.
 */
final class SalesLedger
{
    /** Refund states that represent money actually returned on a return request. */
    public const RETURN_REFUND_STATES = ['partial', 'refunded'];

    /**
     * Payment states that mean the money was captured.
     *
     * @return array<int, string>
     */
    public static function capturedStatuses(): array
    {
        return [
            PaymentStatus::Paid->value,
            PaymentStatus::PartiallyRefunded->value,
            PaymentStatus::Refunded->value,
        ];
    }

    /**
     * One row per eligible order with its refund breakdown resolved. Callers
     * group/aggregate on top of this (by day, customer, method, …).
     *
     * Columns: id, placed_at, user_id, customer_name, customer_email, status,
     * payment_status, payment_method, coupon_id, subtotal, discount_total,
     * tax_total, shipping_total, grand_total, recorded_refund, implied_refund,
     * total_refund, merchandise_refund, other_refund.
     */
    public function orders(ReportFilters $filters): Builder
    {
        $recordedRaw = 'COALESCE(rr.refunded, 0)';
        $recorded = "CASE WHEN {$recordedRaw} > o.grand_total THEN o.grand_total ELSE {$recordedRaw} END";
        $markedRefunded = sprintf(
            "(o.status = '%s' OR o.payment_status = '%s')",
            OrderStatus::Refunded->value,
            PaymentStatus::Refunded->value,
        );
        $implied = "CASE WHEN {$markedRefunded} AND o.grand_total > ({$recorded}) THEN o.grand_total - ({$recorded}) ELSE 0 END";
        $total = "(({$recorded}) + ({$implied}))";
        $merchandise = '(o.subtotal - o.discount_total)';
        $merchRefund = "CASE WHEN {$total} > {$merchandise} THEN {$merchandise} ELSE {$total} END";

        $returnRefunds = DB::table('return_requests')
            ->select('order_id', DB::raw('SUM(refund_amount) as refunded'))
            ->whereIn('refund_status', self::RETURN_REFUND_STATES)
            ->groupBy('order_id');

        $query = DB::table('orders as o')
            ->leftJoinSub($returnRefunds, 'rr', 'rr.order_id', '=', 'o.id')
            ->whereIn('o.payment_status', self::capturedStatuses())
            ->whereBetween('o.placed_at', [$filters->start, $filters->end])
            ->select([
                'o.id', 'o.placed_at', 'o.user_id', 'o.customer_name', 'o.customer_email',
                'o.status', 'o.payment_status', 'o.payment_method', 'o.coupon_id',
                'o.subtotal', 'o.discount_total', 'o.tax_total', 'o.shipping_total', 'o.grand_total',
            ])
            ->selectRaw("{$recorded} as recorded_refund")
            ->selectRaw("{$implied} as implied_refund")
            ->selectRaw("{$total} as total_refund")
            ->selectRaw("{$merchRefund} as merchandise_refund")
            ->selectRaw("{$total} - ({$merchRefund}) as other_refund");

        return $this->applyOrderFilters($query, $filters, 'o');
    }

    /**
     * Totals for the window.
     *
     * @return array{orders: int, gross_sales: float, discounts: float, recorded_refunds: float,
     *     implied_refunds: float, refunds: float, merchandise_refunds: float, tax_shipping_refunds: float,
     *     net_sales: float, tax: float, shipping: float, total_sales: float, order_value: float,
     *     average_order_value: float}
     */
    public function summary(ReportFilters $filters): array
    {
        $row = DB::query()
            ->fromSub($this->orders($filters), 'l')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as discounts')
            ->selectRaw('COALESCE(SUM(tax_total), 0) as tax')
            ->selectRaw('COALESCE(SUM(shipping_total), 0) as shipping')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as order_value')
            ->selectRaw('COALESCE(SUM(recorded_refund), 0) as recorded_refunds')
            ->selectRaw('COALESCE(SUM(implied_refund), 0) as implied_refunds')
            ->selectRaw('COALESCE(SUM(total_refund), 0) as refunds')
            ->selectRaw('COALESCE(SUM(merchandise_refund), 0) as merchandise_refunds')
            ->selectRaw('COALESCE(SUM(other_refund), 0) as tax_shipping_refunds')
            ->first();

        return self::waterfall((array) $row);
    }

    /**
     * Same totals bucketed per day (Y-m-d keys, only days with sales).
     *
     * @return array<string, array<string, float|int>>
     */
    public function daily(ReportFilters $filters): array
    {
        return DB::query()
            ->fromSub($this->orders($filters), 'l')
            ->selectRaw('DATE(placed_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(subtotal) as gross_sales')
            ->selectRaw('SUM(discount_total) as discounts')
            ->selectRaw('SUM(tax_total) as tax')
            ->selectRaw('SUM(shipping_total) as shipping')
            ->selectRaw('SUM(grand_total) as order_value')
            ->selectRaw('SUM(recorded_refund) as recorded_refunds')
            ->selectRaw('SUM(implied_refund) as implied_refunds')
            ->selectRaw('SUM(total_refund) as refunds')
            ->selectRaw('SUM(merchandise_refund) as merchandise_refunds')
            ->selectRaw('SUM(other_refund) as tax_shipping_refunds')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(fn (object $row) => [(string) $row->day => self::waterfall((array) $row)])
            ->all();
    }

    /**
     * Derive the waterfall from summed columns.
     *
     * @param  array<string, mixed>  $sums
     * @return array<string, float|int>
     */
    public static function waterfall(array $sums): array
    {
        $f = fn (string $key): float => round((float) ($sums[$key] ?? 0), 2);

        $orders = (int) ($sums['orders'] ?? 0);
        $gross = $f('gross_sales');
        $discounts = $f('discounts');
        $netSales = round($gross - $discounts - $f('merchandise_refunds'), 2);
        $tax = $f('tax');
        $shipping = $f('shipping');

        return [
            'orders' => $orders,
            'gross_sales' => $gross,
            'discounts' => $discounts,
            'recorded_refunds' => $f('recorded_refunds'),
            'implied_refunds' => $f('implied_refunds'),
            'refunds' => $f('refunds'),
            'merchandise_refunds' => $f('merchandise_refunds'),
            'tax_shipping_refunds' => $f('tax_shipping_refunds'),
            'net_sales' => $netSales,
            'tax' => $tax,
            'shipping' => $shipping,
            'total_sales' => round($netSales + $tax + $shipping - $f('tax_shipping_refunds'), 2),
            'order_value' => $f('order_value'),
            // AOV = (gross − discounts) / orders: what the average order was
            // worth at checkout, before tax/shipping and before any refund.
            'average_order_value' => $orders > 0 ? round(($gross - $discounts) / $orders, 2) : 0.0,
        ];
    }

    /**
     * Optional order-level filters shared by every sales-based report.
     */
    public function applyOrderFilters(Builder $query, ReportFilters $filters, string $alias = 'orders'): Builder
    {
        return $query
            ->when($filters->get('status'), fn (Builder $q, $v) => $q->where("{$alias}.status", $v))
            ->when($filters->get('payment_status'), fn (Builder $q, $v) => $q->where("{$alias}.payment_status", $v))
            ->when($filters->get('payment_method'), fn (Builder $q, $v) => $q->where("{$alias}.payment_method", $v))
            ->when($filters->get('customer'), fn (Builder $q, $v) => $q->where(function (Builder $inner) use ($alias, $v) {
                $inner->where("{$alias}.customer_email", $v)->orWhere("{$alias}.customer_name", $v);
            }));
    }
}
