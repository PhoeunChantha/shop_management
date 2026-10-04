<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

/**
 * Time buckets for report charts and period tables: daily for windows up to
 * ~3 months, monthly beyond, so a year-long range stays readable and bounded.
 * Buckets are gap-filled — a day with no sales still appears as zero.
 */
final class ReportSeries
{
    public const DAILY_MAX_DAYS = 92;

    public static function unit(ReportFilters $filters): string
    {
        return $filters->days() <= self::DAILY_MAX_DAYS ? 'day' : 'month';
    }

    /**
     * Ordered buckets covering the window.
     *
     * @return array<int, array{key: string, label: string, long_label: string}>
     */
    public static function buckets(ReportFilters $filters, ?string $unit = null): array
    {
        $unit ??= self::unit($filters);
        $cursor = $unit === 'day' ? $filters->start->startOfDay() : $filters->start->startOfMonth();
        $last = $unit === 'day' ? $filters->end->startOfDay() : $filters->end->startOfMonth();

        $out = [];
        while ($cursor->lessThanOrEqualTo($last)) {
            $out[] = [
                'key' => $unit === 'day' ? $cursor->toDateString() : $cursor->format('Y-m'),
                'label' => $unit === 'day' ? $cursor->format('M j') : $cursor->format('M Y'),
                'long_label' => $unit === 'day' ? $cursor->format('D, M d') : $cursor->format('F Y'),
            ];
            $cursor = $unit === 'day' ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        return $out;
    }

    /** Bucket key for a Y-m-d date string. */
    public static function keyFor(string $date, string $unit): string
    {
        return $unit === 'day' ? substr($date, 0, 10) : substr($date, 0, 7);
    }
}
