<?php

namespace App\Http\Requests\Size;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Size being updated (null on create) — used to ignore its own code.
     */
    protected function sizeId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        // Normalise the code to uppercase (e.g. "xl" -> "XL") before validating.
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('sizes', 'code')->ignore($this->sizeId()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }
}
