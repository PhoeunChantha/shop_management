<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Services\Admin\SalesReportService;
use Illuminate\Validation\Rule;

class SalesReportRequest extends ReportFilterRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function reportRules(): array
    {
        return [
            'view' => ['nullable', Rule::in(SalesReportService::VIEWS)],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'customer' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function view(): string
    {
        return (string) ($this->validated()['view'] ?? 'summary');
    }
}
