<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

class OverviewReportRequest extends ReportFilterRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function reportRules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ];
    }
}
