<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;

class CommandPaletteService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $term): array
    {
        $term = trim((string) $term);

        return [
            $this->pages($term),
            $this->products($term),
            $this->orders($term),
            $this->customers($term),
            $this->returns($term),
            $this->media($term),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pages(string $term): array
    {
        $pages = collect([
            ['title' => 'Dashboard', 'subtitle' => 'Store overview', 'icon' => 'fa-gauge-high', 'url' => route('admin.dashboard')],
            ['title' => 'Setup Health', 'subtitle' => 'Launch checklist', 'icon' => 'fa-list-check', 'url' => route('admin.setup-health.index')],
            ['title' => 'Reports', 'subtitle' => 'Finance and exports', 'icon' => 'fa-chart-column', 'url' => route('admin.reports.index')],
            ['title' => 'Products', 'subtitle' => 'Catalog management', 'icon' => 'fa-box-open', 'url' => route('admin.products.index')],
            ['title' => 'Orders', 'subtitle' => 'Fulfillment workflow', 'icon' => 'fa-receipt', 'url' => route('admin.orders.index')],
            ['title' => 'Customers', 'subtitle' => 'Buyer CRM', 'icon' => 'fa-user-group', 'url' => route('admin.customers.index')],
            ['title' => 'Returns & Refunds', 'subtitle' => 'Support workflow', 'icon' => 'fa-rotate-left', 'url' => route('admin.returns.index')],
            ['title' => 'Media Library', 'subtitle' => 'Reusable image assets', 'icon' => 'fa-photo-film', 'url' => route('admin.media.index')],
            ['title' => 'Saved Views', 'subtitle' => 'Table filter presets', 'icon' => 'fa-bookmark', 'url' => route('admin.saved-views.index')],
            ['title' => 'Settings', 'subtitle' => 'Store configuration', 'icon' => 'fa-gear', 'url' => route('admin.settings.index')],
        ]);

        if ($term !== '') {
            $needle = mb_strtolower($term);
            $pages = $pages->filter(fn (array $page): bool => str_contains(mb_strtolower($page['title'].' '.$page['subtitle']), $needle));
        }

        return $this->group('Pages', $pages->take(8)->values()->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function products(string $term): array
    {
        if ($term === '') {
            return $this->group('Products', []);
        }

        $items = Product::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
            ->latest('id')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'status'])
            ->map(fn (Product $product): array => [
                'title' => (string) $product->name,
                'subtitle' => trim(($product->sku ?: 'No SKU').' - '.ucfirst((string) $product->status)),
                'icon' => 'fa-box-open',
                'url' => route('admin.products.show', $product->id),
            ])
            ->all();

        return $this->group('Products', $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function orders(string $term): array
    {
        if ($term === '') {
            return $this->group('Orders', []);
        }

        $items = Order::query()
            ->where(function ($query) use ($term): void {
                $query->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            })
            ->latest('id')
            ->limit(6)
            ->get(['id', 'order_number', 'customer_name', 'grand_total', 'status'])
            ->map(fn (Order $order): array => [
                'title' => $order->order_number,
                'subtitle' => $order->customer_name.' - $'.number_format((float) $order->grand_total, 2),
                'icon' => 'fa-receipt',
                'url' => route('admin.orders.show', $order->id),
            ])
            ->all();

        return $this->group('Orders', $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function customers(string $term): array
    {
        if ($term === '') {
            return $this->group('Customers', []);
        }

        $items = CustomerProfile::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->latest('id')
            ->limit(6)
            ->get(['name', 'email', 'phone', 'status'])
            ->map(fn (CustomerProfile $customer): array => [
                'title' => $customer->name ?: $customer->email,
                'subtitle' => trim($customer->email.' - '.($customer->status ? 'Enabled' : 'Disabled')),
                'icon' => 'fa-user-group',
                'url' => route('admin.customers.show', rawurlencode($customer->email)),
            ])
            ->all();

        return $this->group('Customers', $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function returns(string $term): array
    {
        if ($term === '') {
            return $this->group('Returns', []);
        }

        $items = ReturnRequest::query()
            ->with('order:id,order_number,customer_name')
            ->where('return_number', 'like', "%{$term}%")
            ->orWhereHas('order', fn ($query) => $query
                ->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%"))
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (ReturnRequest $return): array => [
                'title' => $return->return_number,
                'subtitle' => ($return->order?->order_number ?: 'No order').' - '.$return->statusLabel(),
                'icon' => 'fa-rotate-left',
                'url' => route('admin.returns.show', $return),
            ])
            ->all();

        return $this->group('Returns', $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function media(string $term): array
    {
        if ($term === '') {
            return $this->group('Media', []);
        }

        $items = MediaAsset::query()
            ->search($term)
            ->latest('id')
            ->limit(6)
            ->get(['folder', 'filename', 'original_name', 'alt_text'])
            ->map(fn (MediaAsset $asset): array => [
                'title' => $asset->original_name ?: basename($asset->filename),
                'subtitle' => $asset->folder.' - '.($asset->alt_text ?: 'No alt text'),
                'icon' => 'fa-photo-film',
                'url' => route('admin.media.index', ['search' => $asset->original_name ?: basename($asset->filename), 'folder' => $asset->folder]),
            ])
            ->all();

        return $this->group('Media', $items);
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @return array<string, mixed>
     */
    private function group(string $label, array $items): array
    {
        return ['label' => $label, 'items' => $items];
    }
}
