<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Size;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Prerequisite data (safe to call repeatedly).
        $this->call([BrandSeeder::class, ProductTagSeeder::class]);
        if (Size::count() === 0) {
            $this->call(SizeSeeder::class);
        }
        if (Color::count() === 0) {
            $this->call(ColorSeeder::class);
        }

        $sizes = Size::all();
        $colors = Color::all();
        $tagIds = ProductTag::pluck('id');
        $brandIds = Brand::pluck('id');

        if (Product::count() === 0) {
            Product::factory()->count(12)->create()->each(function (Product $product) use ($sizes, $colors) {
                $this->addVariants($product, $sizes, $colors);
                $this->addSpecs($product);
                $this->attachTags($product);
            });

            $this->command?->info('Seeded 12 products with variants, specs and tags.');

            return;
        }

        // Non-destructive backfill for products created before this upgrade.
        Product::with(['variants', 'specifications', 'tags'])->get()->each(function (Product $product) use ($sizes, $colors, $tagIds, $brandIds) {
            if (! $product->brand_id && $brandIds->isNotEmpty()) {
                $product->update(['brand_id' => $brandIds->random()]);
            }
            if ($product->variants->isEmpty()) {
                $this->addVariants($product, $sizes, $colors);
            }
            if ($product->specifications->isEmpty()) {
                $this->addSpecs($product);
            }
            if ($product->tags->isEmpty()) {
                $this->attachTags($product);
            }
        });

        $this->command?->info('Backfilled existing products with brand, specs and tags.');
    }

    private function addVariants(Product $product, $sizes, $colors): void
    {
        $variantSizes = $sizes->random(min(rand(1, 3), $sizes->count()));
        $variantColors = $colors->random(min(rand(1, 2), $colors->count()));

        foreach ($variantSizes as $size) {
            foreach ($variantColors as $color) {
                $product->variants()->create([
                    'size_id' => $size->id,
                    'color_id' => $color->id,
                    'sku' => sprintf('UT-%04d-%s-%d', $product->id, $size->code ?: $size->id, $color->id),
                    'barcode' => (string) random_int(1000000000000, 9999999999999),
                    'stock' => rand(0, 80),
                    'low_stock_alert' => 5,
                    'price' => null,
                    'cost_price' => null,
                    'weight' => null,
                    'status' => true,
                ]);
            }
        }
    }

    private function addSpecs(Product $product): void
    {
        $specs = [
            ['name' => 'Material', 'value' => '100% Cotton'],
            ['name' => 'Fit', 'value' => 'Oversized'],
            ['name' => 'Sleeve', 'value' => 'Short Sleeve'],
        ];

        foreach ($specs as $i => $spec) {
            $product->specifications()->create($spec + ['sort_order' => $i]);
        }
    }

    private function attachTags(Product $product): void
    {
        $ids = ProductTag::inRandomOrder()->limit(rand(1, 3))->pluck('id');
        $product->tags()->syncWithoutDetaching($ids);
    }
}
