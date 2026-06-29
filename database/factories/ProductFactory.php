<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(rand(2, 3), true));
        $price = $this->faker->randomElement([19.99, 24.99, 29.99, 34.99, 39.99, 44.99, 49.99, 59.99]);

        // ~40% of products carry a discount.
        $discountType = $this->faker->boolean(40)
            ? $this->faker->randomElement(['fixed', 'percentage'])
            : null;

        $discountAmount = match ($discountType) {
            'fixed' => $this->faker->randomElement([5, 10, 15]),
            'percentage' => $this->faker->randomElement([10, 15, 20, 25, 30]),
            default => 0,
        };

        return [
            'category_id' => Category::inRandomOrder()->value('id') ?? Category::factory(),
            'sub_category_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => $this->faker->paragraph(),
            'price' => $price,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'status' => $this->faker->boolean(85) ? 'active' : 'inactive',
        ];
    }

    /**
     * Force the product active.
     */
    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
