<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure the dropdown data exists (safe when run standalone).
        if (Size::count() === 0) {
            $this->call(SizeSeeder::class);
        }
        if (Color::count() === 0) {
            $this->call(ColorSeeder::class);
        }

        if (Product::count() > 0) {
            $this->command?->warn('Products already exist — skipping ProductSeeder.');

            return;
        }

        $sizes = Size::all();
        $colors = Color::all();

        Product::factory()->count(12)->create()->each(function (Product $product) use ($sizes, $colors) {
            // 1–3 sizes × 1–2 colors → a handful of variants per product.
            $variantSizes = $sizes->random(min(rand(1, 3), $sizes->count()));
            $variantColors = $colors->random(min(rand(1, 2), $colors->count()));

            foreach ($variantSizes as $size) {
                foreach ($variantColors as $color) {
                    $product->variants()->create([
                        'size_id' => $size->id,
                        'color_id' => $color->id,
                        // Deterministically unique: product id + size + color.
                        'sku' => sprintf('UT-%04d-%s-%d', $product->id, $size->code ?: $size->id, $color->id),
                        'stock' => rand(0, 80),
                        'price' => null, // inherit the product price
                    ]);
                }
            }
        });

        $this->command?->info('Seeded 12 products with variants.');
    }
}
