<?php

namespace Database\Seeders;

use App\Models\ProductTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['Cotton', 'Oversized', 'Limited', 'Summer', 'Eco', 'Unisex', 'Premium', 'Streetwear'];

        foreach ($tags as $name) {
            ProductTag::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => true],
            );
        }
    }
}
