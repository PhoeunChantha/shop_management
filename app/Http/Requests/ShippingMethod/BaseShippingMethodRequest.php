<?php

namespace App\Http\Requests\ShippingMethod;

use App\Enums\ShippingRateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ShippingRateType::class)],
            'rate' => ['nullable', 'required_unless:type,free', 'numeric', 'min:0', 'max:1000000'],
            'free_over_amount' => ['nullable', 'required_if:type,free_over', 'numeric', 'min:0', 'max:1000000'],
            'delivery_time' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'boolean'],
        ];
    }
}
