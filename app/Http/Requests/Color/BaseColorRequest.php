<?php

namespace App\Http\Requests\Color;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    /**
     * Color being updated (null on create) — used to ignore its own code.
     */
    protected function colorId(): ?int
    {
        return null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('colors', 'code')->ignore($this->colorId()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }
}
