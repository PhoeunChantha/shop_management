<?php

namespace App\Http\Requests\Color;

class UpdateColorRequest extends BaseColorRequest
{
    /**
     * Ignore the current color's own code during the unique check.
     */
    protected function colorId(): ?int
    {
        return (int) $this->route('id');
    }
}
