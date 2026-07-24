<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SupplierService
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('purchaseOrders')
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($query) => $query->where('status', (bool) $filters['status']))
            ->latest()
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
