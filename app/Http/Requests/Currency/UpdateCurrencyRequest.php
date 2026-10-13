<?php

namespace App\Http\Requests\Currency;

class UpdateCurrencyRequest extends BaseCurrencyRequest
{
    protected function currencyId(): ?int
    {
        return (int) $this->route('id');
    }
}
