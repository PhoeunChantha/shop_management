<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Top-level categories, each with an optional list of sub-categories.
     * Idempotent: matched on slug, so re-running only fills in what is missing.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'T-Shirts', 'icon' => 'fa-shirt', 'description' => 'Everyday classic and graphic tees.',
                'children' => [
                    ['name' => 'Graphic Tees', 'icon' => 'fa-palette', 'description' => 'Printed and illustrated designs.'],
                    ['name' => 'Plain Tees', 'icon' => 'fa-shirt', 'description' => 'Solid-colour basics.'],
                    ['name' => 'Oversized Tees', 'icon' => 'fa-expand', 'description' => 'Relaxed, boxy fits.'],
                ],
            ],
            [
                'name' => 'Hoodies', 'icon' => 'fa-mitten', 'description' => 'Cozy pullovers and zip-ups.',
                'children' => [
                    ['name' => 'Pullover Hoodies', 'icon' => 'fa-mitten', 'description' => 'Classic over-the-head hoodies.'],
                    ['name' => 'Zip-Up Hoodies', 'icon' => 'fa-mitten', 'description' => 'Full-zip layers.'],
                ],
            ],
            ['name' => 'Polo Shirts', 'icon' => 'fa-shirt', 'description' => 'Smart-casual collared shirts.'],
            ['name' => 'Tank Tops', 'icon' => 'fa-vest', 'description' => 'Sleeveless summer essentials.'],
            ['name' => 'Long Sleeves', 'icon' => 'fa-shirt', 'description' => 'Layer-ready long sleeve tees.'],
            [
                'name' => 'Accessories', 'icon' => 'fa-hat-cowboy', 'description' => 'Caps, bags and extras.',
                'children' => [
                    ['name' => 'Caps & Hats', 'icon' => 'fa-hat-cowboy', 'description' => 'Snapbacks, beanies and bucket hats.'],
                    ['name' => 'Bags', 'icon' => 'fa-bag-shopping', 'description' => 'Totes and backpacks.'],
                ],
            ],
        ];

        foreach ($categories as $index => $data) {
            $parent = $this->upsert($data, $index + 1);

            foreach ($data['children'] ?? [] as $childIndex => $child) {
                $this->upsert($child, $childIndex + 1, $parent->id);
            }
        }
    }

    /**
     * @param  array{name: string, icon: string, description: string}  $data
     */
    private function upsert(array $data, int $sortOrder, ?int $parentId = null): Category
    {
        return Category::updateOrCreate(
            ['slug' => Str::slug($data['name'])],
            [
                'parent_id' => $parentId,
                'name' => $data['name'],
                'description' => $data['description'],
                'icon' => $data['icon'],
                'sort_order' => $sortOrder,
                'status' => true,
            ]
        );
    }
}
