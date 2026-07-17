<?php

namespace App\Http\Requests\DealCampaign;

use App\Models\DealCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseDealCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'priority' => $this->input('priority', 0),
            'discount_value' => $this->filled('discount_type') ? $this->input('discount_value', 0) : 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', Rule::in(array_keys(DealCampaign::TYPES))],
            'badge' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'image_media' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'Percentage discounts cannot exceed 100%.');
            }
        });
    }
}
