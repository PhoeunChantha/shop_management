<?php

namespace App\Services\Frontend;

use App\Enums\ReviewStatus;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountService
{
    public function __construct(
        private readonly ProductService $products,
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
            'phone' => $user ? (string) CustomerProfile::where('email', $user->email)->value('phone') : '',
            'tier' => 'Standard',
            'points' => 0,
        ];
    }

    /**
     * Update the customer's name (users table) and phone (customer profile).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): void
    {
        $name = trim($data['first_name'].' '.($data['last_name'] ?? ''));

        $user->update(['name' => $name]);

        CustomerProfile::updateOrCreate(
            ['email' => $user->email],
            ['name' => $name, 'phone' => $data['phone'] ?? null],
        );
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
     * The raw Order model (scoped to the signed-in customer) for the invoice PDF.
     */
    public function findOrderModel(string $id): ?Order
    {
        return $this->orderQuery()
            ->where(function ($query) use ($id): void {
                $query->where('id', $id)->orWhere('order_number', $id);
            })
            ->first();
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
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $ids = $user->wishlist()->pluck('products.id')->all();

        if ($ids === []) {
            return [];
        }

        return $this->products->mappedActiveProducts()
            ->whereIn('id', $ids)
            ->values()
            ->all();
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
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return $user->addresses()
            ->get()
            ->map(fn (Address $address): array => [
                'id' => $address->id,
                'label' => $address->label ?: 'Address',
                'default' => $address->is_default,
                'name' => $address->name,
                'phone' => $address->phone ?: '',
                'street' => $address->street,
                'line' => $address->street,
                'city' => $address->city ?: '',
                'zip' => $address->zip ?: '',
                'country' => $address->country ?: '',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function notifications(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return $user->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($notification): array {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'order',
                    'icon' => $data['icon'] ?? 'bell',
                    'title' => $data['title'] ?? 'Notification',
                    'body' => $data['body'] ?? '',
                    'url' => $data['url'] ?? null,
                    'time' => $notification->created_at?->diffForHumans() ?? '',
                    'unread' => $notification->read_at === null,
                ];
            })
            ->all();
    }

    public function unreadNotifications(): int
    {
        return Auth::user()?->unreadNotifications()->count() ?? 0;
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

    /**
     * @param  array<string, mixed>  $order  A mapped order (from findOrder).
     */
    public function orderContainsProduct(array $order, int $productId): bool
    {
        return collect($order['items'] ?? [])
            ->contains(fn (array $item): bool => (int) ($item['pid'] ?? 0) === $productId);
    }

    public function hasReviewed(User $user, int $productId): bool
    {
        return Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Create a verified-buyer review, pending admin moderation.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitReview(User $user, int $productId, array $data): Review
    {
        return Review::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'status' => ReviewStatus::Pending,
            'is_verified' => true,
        ]);
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
