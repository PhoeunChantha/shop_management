<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderDetail>
 */
final class OrderDetailFactory extends Factory
{
    protected $model = OrderDetail::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 15, 80);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'product_id' => null,
            'product_variant_id' => null,
            'name' => fake()->words(3, true),
            'variant_label' => null,
            'sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'image' => null,
            'price' => $price,
            'quantity' => $quantity,
            'line_total' => round($price * $quantity, 2),
        ];
    }
}
