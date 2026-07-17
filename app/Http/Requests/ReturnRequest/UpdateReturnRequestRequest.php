<?php

namespace App\Http\Requests\ReturnRequest;

use App\Models\ReturnRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(ReturnRequest::STATUSES))],
            'refund_status' => ['required', Rule::in(array_keys(ReturnRequest::REFUND_STATUSES))],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
