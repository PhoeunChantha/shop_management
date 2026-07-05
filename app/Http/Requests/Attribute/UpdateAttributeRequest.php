<?php

namespace App\Http\Requests\Attribute;

class UpdateAttributeRequest extends BaseAttributeRequest
{
    protected function attributeId(): ?int
    {
        return (int) $this->route('id');
    }
}
