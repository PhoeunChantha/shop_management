<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership / Policy is enforced in the controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['body' => trim((string) $this->input('body', ''))]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('Type a message first.'),
            'body.max' => __('Messages are limited to 2000 characters.'),
        ];
    }
}
