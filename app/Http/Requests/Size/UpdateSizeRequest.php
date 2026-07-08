<?php

namespace App\Http\Requests\Size;

class UpdateSizeRequest extends BaseSizeRequest
{
    /**
     * Ignore the current size's own code during the unique check.
     */
    protected function sizeId(): ?int
    {
        return (int) $this->route('id');
    }
}
