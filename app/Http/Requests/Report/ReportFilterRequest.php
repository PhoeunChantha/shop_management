<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Enums\ReportPreset;
use App\Services\Admin\Reports\ReportFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for every report page and export: date window, search,
 * sort and pagination. Reports with extra filters extend this and add them in
 * {@see self::reportRules()}; authorization stays on the route.
 */
class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => ['nullable', Rule::enum(ReportPreset::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50,100'],
            'format' => ['nullable', 'string', 'in:csv,pdf'],
            ...$this->reportRules(),
        ];
    }

    /**
     * Report-specific filter rules.
     *
     * @return array<string, mixed>
     */
    protected function reportRules(): array
    {
        return [];
    }

    public function filters(ReportPreset $default = ReportPreset::Last30Days): ReportFilters
    {
        return ReportFilters::fromArray(
            collect($this->validated())->except('format')->all(),
            $default,
        );
    }

    public function exportFormat(): string
    {
        return (string) ($this->validated()['format'] ?? 'csv');
    }
}
