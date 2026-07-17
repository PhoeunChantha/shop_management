<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('create purchase orders') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'status' => ['required', Rule::in(['draft', 'ordered'])],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stockable' => ['nullable', 'string', 'max:50'],
            'items.*.quantity_ordered' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ];
    }
}
