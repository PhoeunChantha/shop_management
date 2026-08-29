<?php

namespace App\Http\Requests\Category;

class UpdateCategoryRequest extends BaseCategoryRequest
{
    protected function categoryId(): ?int
    {
        $id = $this->route('id');

        return $id ? (int) $id : null;
    }
}
