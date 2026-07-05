<?php

namespace App\Http\Requests\Coupon;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

abstract class BaseCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    /**
     * Coupon being updated (null on create) — used to ignore its own code.
     */
    protected function couponId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        // Normalise the code to a clean, uppercase token.
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($this->couponId()),
            ],
            'type' => ['required', new Enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters, numbers, dashes and underscores.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Percentage coupons can't exceed 100%.
            if ($this->input('type') === CouponType::Percentage->value && (float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'A percentage coupon cannot exceed 100%.');
            }

            // End date must be after the start date.
            $starts = $this->input('starts_at');
            $expires = $this->input('expires_at');

            if ($starts && $expires && strtotime($expires) < strtotime($starts)) {
                $validator->errors()->add('expires_at', 'The expiry date must be after the start date.');
            }
        });
    }
}
