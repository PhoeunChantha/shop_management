<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
}
