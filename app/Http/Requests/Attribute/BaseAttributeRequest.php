<?php

namespace App\Http\Requests\Attribute;

use App\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

abstract class BaseAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
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
            'type' => ['required', new Enum(AttributeType::class)],
            'status' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Custom type → free-text value rows.
            'values' => ['array'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['nullable', 'string', 'max:255'],
            'values.*.color_hex' => ['nullable', 'string', 'max:30'],
            'values.*.sort_order' => ['nullable', 'integer'],

            // Size type → pick from managed Sizes.
            'size_ids' => ['required_if:type,size', 'array', 'min:1'],
            'size_ids.*' => ['integer', 'exists:sizes,id'],

            // Color type → pick from managed Colors.
            'color_ids' => ['required_if:type,color', 'array', 'min:1'],
            'color_ids.*' => ['integer', 'exists:colors,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'size_ids.required_if' => 'Select at least one size.',
            'color_ids.required_if' => 'Select at least one color.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // A custom attribute needs at least one non-empty value.
            if ($this->input('type') === AttributeType::Custom->value) {
                $hasValue = collect($this->input('values', []))
                    ->contains(fn ($row) => filled($row['value'] ?? null));

                if (! $hasValue) {
                    $validator->errors()->add('values', 'Add at least one value for a custom attribute.');
                }
            }
        });
    }
}
