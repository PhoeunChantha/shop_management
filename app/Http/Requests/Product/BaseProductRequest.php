<?php

namespace App\Http\Requests\Product;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class BaseProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Product being updated (null on create) — used to ignore its own SKUs.
     */
    protected function productId(): ?int
    {
        return null;
    }

    public function rules(): array
    {
        $primary = app(SettingService::class)->primaryLanguage();

        return [
            // Organization
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:product_tags,id'],
            'new_tags' => ['nullable', 'string', 'max:500'],

            // Basic (translatable: per-language arrays; primary language required)
            'name' => ['required', 'array'],
            'name.'.$primary => ['required', 'string', 'min:2', 'max:255'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'array'],
            'short_description.*' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],

            // Gallery
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'removed_images' => ['nullable', 'array'],
            'removed_images.*' => ['integer'],
            'primary_image_id' => ['nullable', 'integer'],

            // Type
            'product_type' => ['required', 'in:single,variable'],

            // Single-product stock/identity (only when product_type = single)
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->ignore($this->productId()),
            ],
            'stock' => ['required_if:product_type,single', 'nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],

            // Pricing
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            // Shipping
            'weight' => ['nullable', 'numeric', 'min:0'],

            // Publishing
            'status' => ['required', 'in:draft,active,inactive,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_best_seller' => ['nullable', 'boolean'],
            'is_on_sale' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // SEO (translatable)
            'seo_title' => ['nullable', 'array'],
            'seo_title.*' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'array'],
            'seo_description.*' => ['nullable', 'string', 'max:500'],

            // Variants (required when product_type = variable) — a variant is defined
            // by its set of attribute value IDs.
            'variants' => ['required_if:product_type,variable', 'array'],
            'variants.*.value_ids' => ['required', 'array', 'min:1'],
            'variants.*.value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'variants.*.image_existing' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', 'boolean'],

            // Specifications (empty rows are dropped when saving)
            'specifications' => ['nullable', 'array'],
            'specifications.*.name' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],
            'specifications.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required_if' => 'Add attributes and generate at least one variant for a variable product.',
            'variants.*.value_ids.required' => 'Each variant must map to at least one attribute value.',
            'variants.*.stock.required' => 'Stock is required for each variant.',
            'stock.required_if' => 'Stock is required for a single product.',
            'images.*.image' => 'Each gallery file must be an image.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Percentage discount cannot exceed 100%.
            if ($this->discount_type === 'percentage' && (float) $this->discount_amount > 100) {
                $validator->errors()->add('discount_amount', 'Percentage discount cannot exceed 100%.');
            }

            // Provided SKUs must be unique within the form and across other
            // products. Blank SKUs are auto-generated later, so they're skipped.
            $seen = [];

            foreach ((array) $this->input('variants', []) as $i => $variant) {
                $sku = trim($variant['sku'] ?? '');

                if ($sku === '') {
                    continue;
                }

                if (isset($seen[$sku])) {
                    $validator->errors()->add("variants.{$i}.sku", "Duplicate SKU \"{$sku}\" in the variant list.");
                }
                $seen[$sku] = true;

                $exists = DB::table('product_variants')
                    ->where('sku', $sku)
                    ->when($this->productId(), fn ($q) => $q->where('product_id', '!=', $this->productId()))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("variants.{$i}.sku", "SKU \"{$sku}\" is already in use.");
                }
            }
        });
    }
}
