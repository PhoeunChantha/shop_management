<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use App\Services\Admin\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class BaseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    /**
     * Translatable fields arrive as per-language arrays (name[en], name[km] …);
     * only the primary language of the name is required.
     */
    public function rules(): array
    {
        $primary = app(SettingService::class)->primaryLanguage();

        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'array'],
            'name.'.$primary => ['required', 'string', 'min:2', 'max:255'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'image_media' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],

            // SEO
            'seo_title' => ['nullable', 'array'],
            'seo_title.*' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'array'],
            'seo_description.*' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'array'],
            'seo_keywords.*' => ['nullable', 'string', 'max:500'],
            'seo_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'seo_image_media' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Trim every translation and drop empty ones so the JSON column only holds
     * languages that were actually filled in.
     */
    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['name', 'description', 'seo_title', 'seo_description', 'seo_keywords'] as $field) {
            $value = $this->input($field);

            if (! is_array($value)) {
                continue;
            }

            $clean[$field] = array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : $v, $value),
                fn ($v) => $v !== null && $v !== ''
            );
        }

        if ($clean !== []) {
            $this->merge($clean);
        }

        // An empty picker value means "top-level".
        if ($this->input('parent_id') === '' || $this->input('parent_id') === '0') {
            $this->merge(['parent_id' => null]);
        }
    }

    /**
     * A category cannot be nested under itself or under one of its own
     * sub-categories (that would create a cycle in the tree).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = (int) $this->input('parent_id');
            $selfId = $this->categoryId();

            if (! $parentId || ! $selfId) {
                return;
            }

            $self = Category::query()->find($selfId);

            if ($self && ($parentId === $self->id || in_array($parentId, $self->descendantIds(), true))) {
                $validator->errors()->add('parent_id', __('A category cannot be placed under itself or one of its own sub-categories.'));
            }
        });
    }

    /**
     * The category being edited (null when creating).
     */
    protected function categoryId(): ?int
    {
        return null;
    }

    public function attributes(): array
    {
        $primary = app(SettingService::class)->primaryLanguage();

        return [
            'name.'.$primary => __('category name'),
            'parent_id' => __('parent category'),
        ];
    }
}
