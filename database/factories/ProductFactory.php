<?php

namespace Database\Factories;

use App\Models\Brand;
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

        $discountType = $this->faker->boolean(40)
            ? $this->faker->randomElement(['fixed', 'percentage'])
            : null;

        $discountAmount = match ($discountType) {
            'fixed' => $this->faker->randomElement([5, 10, 15]),
            'percentage' => $this->faker->randomElement([10, 15, 20, 25, 30]),
            default => 0,
        };

        return [
            'category_id' => Category::inRandomOrder()->value('id'),
            'sub_category_id' => null,
            'brand_id' => Brand::inRandomOrder()->value('id'),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'short_description' => $this->faker->sentence(8),
            'description' => $this->faker->paragraph(),
            'thumbnail' => null,
            'price' => $price,
            'cost_price' => round($price * 0.45, 2),
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'weight' => $this->faker->randomElement([0.2, 0.3, 0.4, 0.5]),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'draft', 'inactive']),
            'is_featured' => $this->faker->boolean(25),
            'is_new' => $this->faker->boolean(35),
            'is_best_seller' => $this->faker->boolean(20),
            'is_on_sale' => $discountType !== null,
            'sort_order' => 0,
            'seo_title' => $name,
            'seo_description' => $this->faker->sentence(12),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
