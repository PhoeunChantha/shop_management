<?php

namespace App\Http\Requests\Currency;

use App\Services\Admin\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the CurrencyPolicy in the controller.
        return true;
    }

    /**
     * Currency being updated (null on create) — ignored by the unique check.
     */
    protected function currencyId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'status' => $this->boolean('status'),
            'sort_order' => $this->input('sort_order', 0) ?? 0,
        ]);
    }

    public function rules(): array
    {
        $base = strtoupper((string) app(SettingService::class)->currency()['code']);

        return [
            // The base currency lives in Settings → Localization, never here.
            'code' => ['required', 'string', 'regex:/^[A-Z]{3}$/', Rule::notIn([$base]), Rule::unique('currencies', 'code')->ignore($this->currencyId())],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:8'],
            'symbol_position' => ['required', Rule::in(['before', 'after'])],
            'exchange_rate' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'decimals' => ['required', 'integer', Rule::in([0, 2, 3])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => __('Use the 3-letter ISO code, e.g. KHR, EUR, THB.'),
            'code.not_in' => __('This is your base currency. Change it in Settings → Localization.'),
        ];
    }
}
