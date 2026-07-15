<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'image_media' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }
}
