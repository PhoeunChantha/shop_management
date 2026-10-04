<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

use App\Enums\ReportPreset;
use Carbon\CarbonImmutable;

/**
 * The resolved, validated filter state shared by every report: the date
 * window (from a preset or a custom range), the equal-length comparison window
 * before it, and the free-form report-specific filters (status, category, …).
 *
 * Build it once per request via {@see self::fromArray()} and pass it to the
 * report services so every page resolves dates identically.
 */
final readonly class ReportFilters
{
    /**
     * @param  array<string, mixed>  $values  Validated report-specific filters.
     */
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public ReportPreset $preset,
        public array $values = [],
    ) {}

    /**
     * Resolve validated input into a filter object.
     *
     * Precedence: an explicit start/end pair (custom range) wins, then a named
     * preset, then the default window (last 30 days). Reversed custom dates
     * are swapped rather than rejected.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input, ReportPreset $default = ReportPreset::Last30Days): self
    {
        $preset = ReportPreset::tryFrom((string) ($input['preset'] ?? '')) ?? null;
        $hasCustom = filled($input['start_date'] ?? null) || filled($input['end_date'] ?? null);

        if ($hasCustom && ($preset === null || $preset === ReportPreset::Custom)) {
            $end = filled($input['end_date'] ?? null)
                ? CarbonImmutable::parse((string) $input['end_date'])->endOfDay()
                : now()->toImmutable()->endOfDay();
            $start = filled($input['start_date'] ?? null)
                ? CarbonImmutable::parse((string) $input['start_date'])->startOfDay()
                : $end->subDays(29)->startOfDay();

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
            }

            $preset = ReportPreset::Custom;
        } else {
            $preset = ($preset === null || $preset === ReportPreset::Custom) ? $default : $preset;
            [$start, $end] = $preset->range();
        }

        $values = array_diff_key($input, array_flip(['preset', 'start_date', 'end_date']));

        return new self($start, $end, $preset, $values);
    }

    /** A report-specific filter value, or $default when absent/blank. */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->values[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    public function search(): string
    {
        return trim((string) ($this->values['search'] ?? ''));
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->values['per_page'] ?? $default);
    }

    /** Number of calendar days covered (inclusive). */
    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    /**
     * The equal-length window immediately before this one, for
     * period-over-period comparison.
     */
    public function previous(): self
    {
        $prevEnd = $this->start->subDay()->endOfDay();
        $prevStart = $prevEnd->subDays($this->days() - 1)->startOfDay();

        return new self($prevStart, $prevEnd, ReportPreset::Custom, $this->values);
    }

    /** Same filters over a different window. */
    public function withRange(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self($start, $end, ReportPreset::Custom, $this->values);
    }

    /**
     * Filter state echoed back into views and export links.
     *
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return array_filter([
            'preset' => $this->preset->value,
            'start_date' => $this->start->toDateString(),
            'end_date' => $this->end->toDateString(),
            ...$this->values,
        ], fn ($value) => filled($value));
    }
}
