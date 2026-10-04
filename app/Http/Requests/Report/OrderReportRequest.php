<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Services\Admin\OrderReportService;
use Illuminate\Validation\Rule;

class OrderReportRequest extends ReportFilterRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function reportRules(): array
    {
        return [
            'view' => ['nullable', Rule::in(OrderReportService::VIEWS)],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'fulfillment_status' => ['nullable', Rule::enum(FulfillmentStatus::class)],
            'payment_method' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function view(): string
    {
        return (string) ($this->validated()['view'] ?? 'summary');
    }
}
