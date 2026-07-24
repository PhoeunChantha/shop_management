<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\CustomerTag;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $customers = $this->query($filters)
            ->paginate($perPage)
            ->withQueryString();

        $this->enrichProfiles($customers->getCollection());

        return $customers;
    }

    public function profile(string $emailKey): object
    {
        return $this->ordersFor($emailKey)
            ->selectRaw('
                MAX(customer_name) as customer_name,
                MAX(customer_email) as customer_email,
                MAX(customer_phone) as customer_phone,
                MAX(shipping_city) as shipping_city,
                MAX(shipping_country) as shipping_country,
                COUNT(*) as orders_count,
                COALESCE(SUM(grand_total), 0) as lifetime_spend,
                COALESCE(AVG(grand_total), 0) as average_order_value,
                MIN(COALESCE(placed_at, created_at)) as first_order_at,
                MAX(COALESCE(placed_at, created_at)) as last_order_at
            ')
            ->firstOrFail();
    }

    public function crmProfile(string $emailKey): CustomerProfile
    {
        $this->syncProfiles([$emailKey]);

        return CustomerProfile::with('tags')
            ->where('email', $emailKey)
            ->firstOrFail();
    }

    public function tags(): Collection
    {
        return CustomerTag::orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCrm(string $emailKey, array $data): CustomerProfile
    {
        $profile = $this->crmProfile($emailKey);

        $profile->update([
            'notes' => $data['notes'] ?? null,
        ]);

        $profile->tags()->sync($data['tags'] ?? []);

        return $profile->fresh('tags');
    }

    public function orders(string $emailKey): LengthAwarePaginator
    {
        return $this->ordersFor($emailKey)
            ->withSum('details', 'quantity')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();
    }

    public function topProducts(string $emailKey): Collection
    {
        return OrderDetail::query()
            ->selectRaw('
                order_details.name,
                order_details.sku,
                SUM(order_details.quantity) as quantity,
                SUM(order_details.line_total) as revenue
            ')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereRaw('LOWER(orders.customer_email) = ?', [$emailKey])
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();
    }

    /**
     * @param  array<int, string>  $emails
     */
    public function setStatus(array $emails, bool $status): int
    {
        $emailKeys = $this->normalizeEmails($emails);
        $this->syncProfiles($emailKeys);

        return CustomerProfile::query()
            ->whereIn('email', $emailKeys)
            ->update([
                'status' => $status,
                'deleted_at' => null,
            ]);
    }

    /**
     * @param  array<int, string>  $emails
     */
    public function delete(array $emails): int
    {
        $emailKeys = $this->normalizeEmails($emails);
        $this->syncProfiles($emailKeys);

        return CustomerProfile::query()
            ->whereIn('email', $emailKeys)
            ->delete();
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<int, string>
     */
    public function normalizeEmails(array $emails): array
    {
        return collect($emails)
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $emailKeys
     */
    public function exportRows(array $emailKeys): Collection
    {
        return $this->query(['sort' => 'customer_name'])
            ->whereIn(DB::raw('LOWER(orders.customer_email)'), $emailKeys)
            ->get();
    }

    /**
     * @param  array<int, string>  $emailKeys
     * @param  resource  $handle
     */
    public function writeCsv(array $emailKeys, $handle): void
    {
        fputcsv($handle, [
            'Name',
            'Email',
            'Phone',
            'Orders',
            'Lifetime Spend',
            'Average Order',
            'First Order',
            'Last Order',
        ]);

        $this->exportRows($emailKeys)
            ->each(function ($customer) use ($handle): void {
                fputcsv($handle, [
                    $customer->customer_name,
                    $customer->customer_email,
                    $customer->customer_phone,
                    $customer->orders_count,
                    number_format((float) $customer->lifetime_spend, 2, '.', ''),
                    number_format((float) $customer->average_order_value, 2, '.', ''),
                    $customer->first_order_at,
                    $customer->last_order_at,
                ]);
            });
    }

    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        $customerGroups = Order::query()
            ->selectRaw('LOWER(customer_email) as email_key, COUNT(*) as orders_count')
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->groupBy(DB::raw('LOWER(customer_email)'));

        return [
            'customers' => (int) Order::query()
                ->whereNotNull('customer_email')
                ->where('customer_email', '!=', '')
                ->selectRaw('COUNT(DISTINCT LOWER(customer_email)) as aggregate')
                ->value('aggregate'),
            'repeat' => (int) DB::query()
                ->fromSub($customerGroups, 'customer_groups')
                ->where('orders_count', '>', 1)
                ->count(),
            'revenue' => (float) Order::sum('grand_total'),
            'registered' => (int) Order::query()
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $sort = $filters['sort'] ?? 'last_order';

        return Order::query()
            ->leftJoin('customer_profiles as cp', DB::raw('LOWER(orders.customer_email)'), '=', 'cp.email')
            ->selectRaw('
                LOWER(orders.customer_email) as email_key,
                MAX(orders.customer_email) as customer_email,
                MAX(orders.customer_name) as customer_name,
                MAX(orders.customer_phone) as customer_phone,
                COALESCE(MAX(cp.status), 1) as profile_status,
                COUNT(*) as orders_count,
                COALESCE(SUM(orders.grand_total), 0) as lifetime_spend,
                COALESCE(AVG(orders.grand_total), 0) as average_order_value,
                MIN(COALESCE(orders.placed_at, orders.created_at)) as first_order_at,
                MAX(COALESCE(orders.placed_at, orders.created_at)) as last_order_at
            ')
            ->whereNotNull('orders.customer_email')
            ->where('orders.customer_email', '!=', '')
            ->whereNull('cp.deleted_at')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('orders.customer_name', 'like', "%{$search}%")
                        ->orWhere('orders.customer_email', 'like', "%{$search}%")
                        ->orWhere('orders.customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['tag_id'] ?? null, function (Builder $query, int|string $tagId): void {
                $query->whereExists(function ($query) use ($tagId): void {
                    $query->selectRaw('1')
                        ->from('customer_profiles as tag_cp')
                        ->join('customer_profile_tag as cpt', 'cpt.customer_profile_id', '=', 'tag_cp.id')
                        ->whereRaw('tag_cp.email = LOWER(orders.customer_email)')
                        ->where('cpt.customer_tag_id', $tagId)
                        ->whereNull('tag_cp.deleted_at');
                });
            })
            ->groupBy(DB::raw('LOWER(orders.customer_email)'))
            ->when(($filters['spend'] ?? null) === 'new', fn (Builder $query) => $query->havingRaw('COUNT(*) = 1'))
            ->when(($filters['spend'] ?? null) === 'repeat', fn (Builder $query) => $query->havingRaw('COUNT(*) > 1'))
            ->when(($filters['spend'] ?? null) === 'vip', fn (Builder $query) => $query->havingRaw('SUM(orders.grand_total) >= 500'))
            ->when($sort === 'lifetime_spend', fn (Builder $query) => $query->orderByDesc('lifetime_spend'))
            ->when($sort === 'orders_count', fn (Builder $query) => $query->orderByDesc('orders_count'))
            ->when($sort === 'customer_name', fn (Builder $query) => $query->orderBy('customer_name'))
            ->when($sort === 'last_order', fn (Builder $query) => $query->orderByDesc('last_order_at'));
    }

    private function ordersFor(string $emailKey): Builder
    {
        return Order::query()
            ->whereRaw('LOWER(customer_email) = ?', [$emailKey]);
    }

    /**
     * @param  array<int, string>  $emailKeys
     */
    private function syncProfiles(array $emailKeys): void
    {
        if ($emailKeys === []) {
            return;
        }

        Order::query()
            ->selectRaw('
                LOWER(customer_email) as email_key,
                MAX(customer_name) as customer_name,
                MAX(customer_phone) as customer_phone
            ')
            ->whereIn(DB::raw('LOWER(customer_email)'), $emailKeys)
            ->groupBy(DB::raw('LOWER(customer_email)'))
            ->get()
            ->each(function ($snapshot): void {
                $profile = CustomerProfile::withTrashed()->updateOrCreate(
                    ['email' => $snapshot->email_key],
                    [
                        'name' => $snapshot->customer_name,
                        'phone' => $snapshot->customer_phone,
                    ],
                );

                if ($profile->trashed()) {
                    $profile->restore();
                }
            });
    }

    /**
     * @param  Collection<int, object>  $customers
     */
    private function enrichProfiles(Collection $customers): void
    {
        $emailKeys = $customers->pluck('email_key')->filter()->values()->all();
        $this->syncProfiles($emailKeys);

        $profiles = CustomerProfile::with('tags')
            ->whereIn('email', $emailKeys)
            ->get()
            ->keyBy('email');

        $customers->each(function ($customer) use ($profiles): void {
            $profile = $profiles->get($customer->email_key);

            $customer->crm_profile_id = $profile?->id;
            $customer->customer_notes = $profile?->notes;
            $customer->tags = $profile?->tags ?? collect();
        });
    }
}
