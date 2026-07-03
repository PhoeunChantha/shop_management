<?php

namespace App\Http\Requests\Product;

class UpdateProductRequest extends BaseProductRequest
{
    /**
     * Ignore the current product's own variants when checking SKU uniqueness.
     */
    protected function productId(): ?int
    {
        return (int) $this->route('id');
    }
}
