<?php

namespace App\Http\Requests\Supplier;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('edit suppliers') ?? false;
    }
}
