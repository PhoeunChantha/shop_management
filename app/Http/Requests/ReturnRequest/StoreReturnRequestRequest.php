<?php

namespace App\Http\Requests\ReturnRequest;

use App\Models\ReturnRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', Rule::in(array_keys(ReturnRequest::REASONS))],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_detail_id' => ['required', 'integer', 'exists:order_details,id'],
            'items.*.return' => ['nullable', 'boolean'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.condition' => ['nullable', 'string', 'max:120'],
        ];
    }
}
