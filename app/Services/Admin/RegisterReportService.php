<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * New-customer registrations over time. Customers are users holding the
 * 'customer' spatie role; signup date is the account's created_at.
 */
final class RegisterReportService extends ReportService
{
    private const LIST_LIMIT = 50;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        $inRange = $this->customers()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'total_customers' => $this->customers()->count(),
                'in_range' => (clone $inRange)->count(),
                'verified' => (clone $inRange)->whereNotNull('email_verified_at')->count(),
            ],
            'signupsByDay' => $this->signupsByDay($start, $end),
            'recent' => $this->recentSignups($start, $end),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->recentSignups($start, $end)
            ->prepend(['Name' => 'Name', 'Email' => 'Email', 'Registered' => 'Registered', 'Verified' => 'Verified'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    private function customers(): Builder
    {
        return User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', 'customer'));
    }

    /**
     * @return Collection<int, array<string, string|int>>
     */
    private function signupsByDay(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->customers()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as signups')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (object $row): array => [
                'date' => (string) $row->date,
                'signups' => (int) $row->signups,
            ]);
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function recentSignups(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->customers()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->latest('created_at')
            ->limit(self::LIST_LIMIT)
            ->get(['name', 'email', 'created_at', 'email_verified_at'])
            ->map(fn (User $user): array => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'registered' => $user->created_at?->format('Y-m-d H:i') ?? '',
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
            ]);
    }
}
