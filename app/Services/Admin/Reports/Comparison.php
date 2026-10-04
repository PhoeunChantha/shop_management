<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

/**
 * Period-over-period deltas. A change is only reported when the previous
 * period has a non-zero base; otherwise change is null and the UI shows "—"
 * instead of a fabricated +100% / ∞.
 */
final class Comparison
{
    /**
     * @return array{current: float, previous: float, change: float|null, direction: string}
     */
    public static function of(float|int $current, float|int $previous): array
    {
        $cur = (float) $current;
        $prev = (float) $previous;
        $change = abs($prev) > 0.000001 ? round((($cur - $prev) / abs($prev)) * 100, 1) : null;

        return [
            'current' => $cur,
            'previous' => $prev,
            'change' => $change,
            'direction' => match (true) {
                $change === null, $change == 0.0 => 'flat',
                $change > 0 => 'up',
                default => 'down',
            },
        ];
    }

    /**
     * @param  array<string, float|int>  $current
     * @param  array<string, float|int>  $previous
     * @param  array<int, string>  $keys
     * @return array<string, array{current: float, previous: float, change: float|null, direction: string}>
     */
    public static function many(array $current, array $previous, array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            $out[$key] = self::of($current[$key] ?? 0, $previous[$key] ?? 0);
        }

        return $out;
    }

    /** Safe ratio as a percentage; null when the denominator is zero. */
    public static function percent(float|int $part, float|int $whole, int $precision = 1): ?float
    {
        return abs((float) $whole) > 0.000001 ? round(((float) $part / (float) $whole) * 100, $precision) : null;
    }
}
