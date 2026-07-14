<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseBannerRequest extends FormRequest
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
            'image' => [$this->imageRule(), 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'kicker' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * Image is required on create, optional on update (keep existing).
     */
    protected function imageRule(): string
    {
        return 'required';
    }
}
