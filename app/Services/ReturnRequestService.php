<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReturnRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReturnRequestService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return ReturnRequest::query()
            ->with('order:id,order_number,customer_name,customer_email,grand_total,payment_status')
            ->withCount('items')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['refund_status'] ?? null, fn ($query, $status) => $query->where('refund_status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        return [
            'total' => ReturnRequest::count(),
            'requested' => ReturnRequest::where('status', 'requested')->count(),
            'approved' => ReturnRequest::whereIn('status', ['approved', 'received'])->count(),
            'refunds' => (float) ReturnRequest::where('refund_status', 'refunded')->sum('refund_amount'),
        ];
    }

    public function orderOptions(): Collection
    {
        return Order::query()
            ->with('details:id,order_id,name,sku,quantity,price,line_total')
            ->latest()
            ->limit(150)
            ->get(['id', 'order_number', 'customer_name', 'customer_email', 'grand_total']);
    }

    public function orderForCreate(int|string $id): Order
    {
        return Order::with('details')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReturnRequest
    {
        return DB::transaction(function () use ($data): ReturnRequest {
            $order = Order::with('details')->findOrFail($data['order_id']);
            $items = $this->validatedItems($order, $data['items'] ?? []);
            $requestedAmount = array_sum(array_column($items, 'line_total'));

            $return = ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'status' => 'requested',
                'refund_status' => 'not_refunded',
                'reason' => $data['reason'],
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
                'requested_amount' => $requestedAmount,
                'refund_amount' => min((float) ($data['refund_amount'] ?? $requestedAmount), $requestedAmount),
            ]);

            $return->items()->createMany($items);
            $order->logEvent('return', 'Return requested', $return->return_number.' - '.$return->reasonLabel());

            return $return->fresh(['order', 'items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReturnRequest $return, array $data): ReturnRequest
    {
        return DB::transaction(function () use ($return, $data): ReturnRequest {
            $oldStatus = $return->status;
            $oldRefund = $return->refund_status;

            $return->fill([
                'status' => $data['status'],
                'refund_status' => $data['refund_status'],
                'refund_amount' => min((float) ($data['refund_amount'] ?? 0), (float) $return->requested_amount),
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            if ($oldStatus !== $return->status) {
                match ($return->status) {
                    'approved' => $return->approved_at ??= now(),
                    'received' => $return->received_at ??= now(),
                    'refunded' => $return->refunded_at ??= now(),
                    default => null,
                };
            }

            if ($return->refund_status === 'refunded') {
                $return->status = 'refunded';
                $return->refunded_at ??= now();
            }

            $return->save();
            $this->syncOrderPayment($return);

            if ($oldStatus !== $return->status) {
                $return->order->logEvent('return', 'Return '.$return->statusLabel(), $return->return_number);
            }

            if ($oldRefund !== $return->refund_status) {
                $return->order->logEvent('refund', 'Refund '.$return->refundStatusLabel(), '$'.number_format((float) $return->refund_amount, 2));
            }

            return $return->fresh(['order', 'items.orderDetail']);
        });
    }

    public function findForShow(ReturnRequest $return): ReturnRequest
    {
        return $return->load(['order.details', 'order.events.actor', 'items.orderDetail']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $inputItems
     * @return array<int, array<string, mixed>>
     */
    private function validatedItems(Order $order, array $inputItems): array
    {
        $details = $order->details->keyBy('id');
        $items = [];

        foreach ($inputItems as $item) {
            if (empty($item['return'])) {
                continue;
            }

            $detail = $details->get((int) ($item['order_detail_id'] ?? 0));
            if (! $detail instanceof OrderDetail) {
                continue;
            }

            $quantity = min((int) ($item['quantity'] ?? 0), $detail->quantity);
            if ($quantity < 1) {
                continue;
            }

            $lineTotal = round((float) $detail->price * $quantity, 2);
            $items[] = [
                'order_detail_id' => $detail->id,
                'name' => $detail->name,
                'sku' => $detail->sku,
                'quantity' => $quantity,
                'unit_price' => $detail->price,
                'line_total' => $lineTotal,
                'condition' => $item['condition'] ?? null,
            ];
        }

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Select at least one valid order item to return.']);
        }

        return $items;
    }

    private function syncOrderPayment(ReturnRequest $return): void
    {
        if (! in_array($return->refund_status, ['partial', 'refunded'], true)) {
            return;
        }

        $order = $return->order;
        $refunded = (float) $order->returnRequests()
            ->whereIn('refund_status', ['partial', 'refunded'])
            ->sum('refund_amount');

        $order->payment_status = $refunded >= (float) $order->grand_total
            ? PaymentStatus::Refunded
            : PaymentStatus::PartiallyRefunded;

        if ($order->payment_status === PaymentStatus::Refunded) {
            $order->status = OrderStatus::Refunded;
        }

        $order->save();
    }
}
