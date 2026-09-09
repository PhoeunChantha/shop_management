<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the OrderPolicy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'fulfillment_status' => ['required', new Enum(FulfillmentStatus::class)],
            'payment_status' => ['nullable', new Enum(PaymentStatus::class)],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'shipped_at' => ['nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Reject a status change that isn't a valid transition from the order's
     * current status (e.g. Delivered -> Pending, or out of a terminal state).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $newStatus = OrderStatus::tryFrom((string) $this->input('status'));

            if (! $newStatus) {
                return; // already caught by the required/Enum rule
            }

            $order = Order::find($this->route('id'));

            if (! $order || $newStatus === $order->status) {
                return; // not found (404 handled by the controller) or unchanged
            }

            if (! in_array($newStatus, $order->status->transitionsTo(), true)) {
                $validator->errors()->add('status', sprintf(
                    'Cannot move an order from "%s" to "%s".',
                    $order->status->label(),
                    $newStatus->label(),
                ));
            }
        });
    }
}
