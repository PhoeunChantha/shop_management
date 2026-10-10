<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Credit or debit a customer's wallet from the admin "Adjust balance" modal.
 * On failure the request redirects back with old input, which reopens the
 * modal (its hidden form_mode field comes back via old()).
 */
final class AdjustWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gated in the controller (`edit wallets` permission).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['user_id' => 'customer'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = User::find($this->integer('user_id'));

            if (! $user?->hasRole('customer')) {
                $validator->errors()->add('user_id', __('Choose a customer.'));

                return;
            }

            // A debit may not take the wallet below zero.
            if ($this->input('direction') === 'debit' && (float) $this->input('amount') > (float) $user->wallet_balance) {
                $validator->errors()->add('amount', __('This customer only has :balance in their wallet.', [
                    'balance' => '$'.number_format((float) $user->wallet_balance, 2),
                ]));
            }
        });
    }

    public function customer(): User
    {
        return User::findOrFail($this->integer('user_id'));
    }
}
