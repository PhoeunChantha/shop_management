<?php

namespace App\Http\Requests\Attribute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    protected function attributeId(): ?int
    {
        return null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('attributes', 'name')->ignore($this->attributeId()),
            ],
            'status' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'values' => ['required', 'array', 'min:1'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'string', 'max:255'],
            'values.*.color_hex' => ['nullable', 'string', 'max:30'],
            'values.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'values.required' => 'Add at least one value for this attribute.',
            'values.min' => 'Add at least one value for this attribute.',
            'values.*.value.required' => 'Each value needs a label.',
        ];
    }
}
