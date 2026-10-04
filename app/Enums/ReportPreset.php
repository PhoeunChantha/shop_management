<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * Named date windows offered by every report filter bar. Weeks start on
 * Monday; "last 7/30 days" include today.
 */
enum ReportPreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisWeek = 'this_week';
    case LastWeek = 'last_week';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisQuarter = 'this_quarter';
    case ThisYear = 'this_year';
    case LastYear = 'last_year';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Yesterday => 'Yesterday',
            self::Last7Days => 'Last 7 days',
            self::Last30Days => 'Last 30 days',
            self::ThisWeek => 'This week',
            self::LastWeek => 'Last week',
            self::ThisMonth => 'This month',
            self::LastMonth => 'Last month',
            self::ThisQuarter => 'This quarter',
            self::ThisYear => 'This year',
            self::LastYear => 'Last year',
            self::Custom => 'Custom range',
        };
    }

    /**
     * Resolve the preset against "now". Custom has no intrinsic window.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function range(?CarbonImmutable $now = null): ?array
    {
        $now ??= now()->toImmutable();
        $today = $now->startOfDay();

        return match ($this) {
            self::Today => [$today, $today->endOfDay()],
            self::Yesterday => [$today->subDay(), $today->subDay()->endOfDay()],
            self::Last7Days => [$today->subDays(6), $today->endOfDay()],
            self::Last30Days => [$today->subDays(29), $today->endOfDay()],
            self::ThisWeek => [$now->startOfWeek(), $now->endOfWeek()],
            self::LastWeek => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            self::ThisMonth => [$now->startOfMonth(), $now->endOfMonth()],
            self::LastMonth => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            self::ThisQuarter => [$now->startOfQuarter(), $now->endOfQuarter()],
            self::ThisYear => [$now->startOfYear(), $now->endOfYear()],
            self::LastYear => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            self::Custom => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $preset) => [$preset->value => __($preset->label())])
            ->all();
    }
}
