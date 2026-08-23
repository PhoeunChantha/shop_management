<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SupplierService
{
    /** Purchase-order activity filters for the supplier list (key => label). */
    public const PO_FILTERS = [
        'any' => 'Has purchase orders',
        'open' => 'Has open orders',
        'none' => 'No purchase orders',
    ];

    /** Contact-detail completeness filters (key => label). */
    public const CONTACT_FILTERS = [
        'complete' => 'Complete profile',
        'no_email' => 'Missing email',
        'no_phone' => 'Missing phone',
        'no_address' => 'Missing address',
    ];

    /** Sort options for the supplier list (key => label). */
    public const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'name_asc' => 'Name A–Z',
        'name_desc' => 'Name Z–A',
        'orders_desc' => 'Most purchase orders',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('purchaseOrders')
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $query) => $query->where('status', (bool) $filters['status']))
            ->when($filters['orders'] ?? null, fn (Builder $query, string $orders) => match ($orders) {
                'any' => $query->has('purchaseOrders'),
                'open' => $query->whereHas('purchaseOrders', fn (Builder $po) => $po->whereIn('status', ['draft', 'ordered', 'partial'])),
                'none' => $query->doesntHave('purchaseOrders'),
                default => $query,
            })
            ->when($filters['contact'] ?? null, fn (Builder $query, string $contact) => match ($contact) {
                'complete' => $query->whereNotNull('email')->where('email', '!=', '')
                    ->whereNotNull('phone')->where('phone', '!=', '')
                    ->whereNotNull('address')->where('address', '!=', ''),
                'no_email' => $query->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', '')),
                'no_phone' => $query->where(fn (Builder $q) => $q->whereNull('phone')->orWhere('phone', '')),
                'no_address' => $query->where(fn (Builder $q) => $q->whereNull('address')->orWhere('address', '')),
                default => $query,
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $to) => $query->whereDate('created_at', '<=', $to))
            ->tap(fn (Builder $query) => match ($filters['sort'] ?? 'newest') {
                'oldest' => $query->oldest(),
                'name_asc' => $query->orderBy('name'),
                'name_desc' => $query->orderByDesc('name'),
                'orders_desc' => $query->orderByDesc('purchase_orders_count')->orderBy('name'),
                default => $query->latest(),
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        return [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', true)->count(),
            'inactive' => Supplier::where('status', false)->count(),
        ];
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchaseOrders()->exists()) {
            throw new \InvalidArgumentException('This supplier has purchase orders and cannot be deleted.');
        }

        $supplier->delete();
    }
}
