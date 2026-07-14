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
    private const DEMO_THUMBNAILS = [
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1622445275576-721325763afe?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1562157873-818bc0726f68?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1554568218-0f1715e72254?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1564859228273-274232fdb516?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1576566588028-4147f3842f27?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1627225924765-552d49cf47ad?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1571945153237-4929e783af4a?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=900&q=82',
    ];

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
            Product::factory()->count(12)->create()->each(function (Product $product, int $index) use ($sizes, $colors) {
                $this->addThumbnail($product, $index);
                $this->addVariants($product, $sizes, $colors);
                $this->addSpecs($product);
                $this->attachTags($product);
            });

            $this->command?->info('Seeded 12 products with variants, specs and tags.');

            return;
        }

        // Non-destructive backfill for products created before this upgrade.
        Product::with(['variants', 'specifications', 'tags'])->get()->each(function (Product $product, int $index) use ($sizes, $colors, $tagIds, $brandIds) {
            if (! $product->brand_id && $brandIds->isNotEmpty()) {
                $product->update(['brand_id' => $brandIds->random()]);
            }
            if (! $product->thumbnail) {
                $this->addThumbnail($product, $index);
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

        $this->command?->info('Backfilled existing products with brand, thumbnails, specs and tags.');
    }

    private function addThumbnail(Product $product, int $index): void
    {
        $product->forceFill([
            'thumbnail' => self::DEMO_THUMBNAILS[$index % count(self::DEMO_THUMBNAILS)],
        ])->save();
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
