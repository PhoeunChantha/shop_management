<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FrontendAccountService
{
    public function __construct(
        private readonly FrontendProductService $products,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function user(): array
    {
        $user = Auth::user();
        $name = trim((string) ($user?->name ?: 'Guest Customer'));
        $parts = preg_split('/\s+/', $name) ?: [];
        $first = $parts[0] ?? 'Guest';
        $last = trim(Str::after($name, $first)) ?: 'Customer';

        return [
            'name' => $name,
            'first' => $first,
            'last' => $last,
            'email' => $user?->email ?: 'guest@example.com',
            'phone' => '',
            'tier' => 'Standard',
            'points' => 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function orders(): array
    {
        return $this->orderQuery()
            ->latest('placed_at')
            ->latest()
            ->get()
            ->map(fn (Order $order): array => $this->mapOrder($order))
            ->values()
            ->all();
    }

    public function findOrder(string $id): ?array
    {
        $order = $this->orderQuery()
            ->where(function ($query) use ($id): void {
                $query->where('id', $id)->orWhere('order_number', $id);
            })
            ->first();

        return $order ? $this->mapOrder($order) : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function productsById(): Collection
    {
        return $this->products->mappedActiveProducts()->keyBy('id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function wishlistProducts(): array
    {
        return $this->products->mappedActiveProducts()->all();
    }

    /**
     * @return array<string, array{name: string, hex: string}>
     */
    public function colors(): array
    {
        return $this->products->colors();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function addresses(): array
    {
        return collect($this->orders())
            ->filter(fn (array $order): bool => filled($order['address'] ?? null))
            ->unique('address')
            ->values()
            ->map(fn (array $order, int $index): array => [
                'label' => $index === 0 ? 'Shipping' : 'Previous',
                'default' => $index === 0,
                'name' => $this->user()['name'],
                'line' => $order['address'],
                'city' => '',
                'country' => '',
                'phone' => $this->user()['phone'],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function notifications(): array
    {
        return collect($this->orders())
            ->take(6)
            ->map(fn (array $order): array => [
                'type' => 'order',
                'icon' => 'box',
                'title' => 'Order #UT-'.$order['id'].' '.$order['status'],
                'body' => 'Your order total is $'.number_format((float) $order['total'], 2).'.',
                'time' => $order['date'],
                'unread' => $order['status'] !== 'Delivered',
            ])
            ->values()
            ->all();
    }

    public function unreadNotifications(): int
    {
        return collect($this->notifications())->where('unread', true)->count();
    }

    public function findProduct(int $id): ?array
    {
        $product = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->find($id);

        return $product ? $this->products->map($product) : null;
    }

    private function orderQuery()
    {
        $user = Auth::user();

        return Order::query()
            ->with([
                'details.product' => fn ($query) => $query->with($this->products->relations()),
                'details.variant.color:id,name,code,hex_code',
                'details.variant.size:id,name,code',
            ])
            ->when(
                $user,
                fn ($query, User $user) => $query->where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id)->orWhere('customer_email', $user->email);
                }),
                fn ($query) => $query->whereRaw('1 = 0')
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOrder(Order $order): array
    {
        $status = $order->status?->label() ?? ucfirst((string) $order->status);
        $stage = max(1, ($order->status?->flowIndex() ?? 0) + 1);

        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'date' => ($order->placed_at ?? $order->created_at)?->format('M j, Y') ?? '',
            'status' => $status,
            'stage' => $stage,
            'total' => (float) $order->grand_total,
            'address' => collect([
                $order->shipping_address,
                $order->shipping_city,
                $order->shipping_zip,
                $order->shipping_country,
            ])->filter()->join(', '),
            'courier' => $order->carrier ?: $order->shipping_method ?: 'Standard shipping',
            'tracking' => $order->tracking_number ?: 'Pending',
            'eta' => $order->fulfilled_at?->format('M j, Y')
                ?: $order->shipped_at?->addDays(3)->format('M j, Y')
                ?: 'Pending',
            'items' => $order->details->map(fn ($detail): array => [
                'pid' => $detail->product_id,
                'name' => $detail->name,
                'color' => strtolower($detail->variant?->color?->code ?: 'black'),
                'size' => $detail->variant?->size?->code ?: $detail->variant_label ?: 'One Size',
                'price' => (float) $detail->price,
                'qty' => (int) $detail->quantity,
            ])->values()->all(),
        ];
    }
}
