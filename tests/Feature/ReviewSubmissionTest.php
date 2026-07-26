<?php

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\Admin\ReviewService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    $this->product = Product::factory()->create(['status' => 'active']);
    $this->order = Order::factory()->status(OrderStatus::Delivered)->create([
        'user_id' => $this->customer->id,
        'payment_status' => 'paid',
    ]);
    OrderDetail::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
    ]);
});

function submitReview(array $overrides = []): array
{
    return array_merge([
        'rating' => 5,
        'title' => 'Great tee',
        'body' => 'Really loved the quality and the fit.',
    ], $overrides);
}

it('creates a verified, pending review from a purchased order', function () {
    $this->actingAs($this->customer)
        ->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $this->product->id]), submitReview())
        ->assertRedirect(route('frontend.account.orders'));

    $review = Review::first();
    expect($review)->not->toBeNull()
        ->and($review->product_id)->toBe($this->product->id)
        ->and($review->user_id)->toBe($this->customer->id)
        ->and($review->rating)->toBe(5)
        ->and($review->is_verified)->toBeTrue()
        ->and($review->status)->toBe(ReviewStatus::Pending);
});

it('does not affect the product rating until approved', function () {
    $this->actingAs($this->customer)
        ->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $this->product->id]), submitReview());

    expect((int) $this->product->refresh()->rating_count)->toBe(0);

    app(ReviewService::class)->moderate(Review::first(), ReviewStatus::Approved);

    expect((int) $this->product->refresh()->rating_count)->toBe(1)
        ->and((float) $this->product->rating_avg)->toBe(5.0);
});

it('prevents reviewing the same product twice', function () {
    Review::create([
        'product_id' => $this->product->id, 'user_id' => $this->customer->id,
        'author_name' => 'x', 'rating' => 4, 'body' => 'first review here', 'status' => ReviewStatus::Pending, 'is_verified' => true,
    ]);

    $this->actingAs($this->customer)
        ->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $this->product->id]), submitReview())
        ->assertRedirect(route('frontend.account.orders'));

    expect(Review::where('product_id', $this->product->id)->where('user_id', $this->customer->id)->count())->toBe(1);
});

it('404s when the product is not in the order', function () {
    $other = Product::factory()->create(['status' => 'active']);

    $this->actingAs($this->customer)
        ->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $other->id]), submitReview())
        ->assertNotFound();
});

it('validates rating and body', function () {
    $this->actingAs($this->customer)
        ->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $this->product->id]), ['rating' => 0, 'body' => 'short'])
        ->assertInvalid(['rating', 'body']);
});

it('requires authentication', function () {
    $this->post(route('frontend.account.orders.review.store', ['id' => $this->order->id, 'pid' => $this->product->id]), submitReview())
        ->assertRedirect(route('frontend.login'));
});
