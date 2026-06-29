<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'T-Shirts', 'icon' => 'fa-shirt', 'description' => 'Everyday classic and graphic tees.'],
            ['name' => 'Hoodies', 'icon' => 'fa-mitten', 'description' => 'Cozy pullovers and zip-ups.'],
            ['name' => 'Polo Shirts', 'icon' => 'fa-shirt', 'description' => 'Smart-casual collared shirts.'],
            ['name' => 'Tank Tops', 'icon' => 'fa-vest', 'description' => 'Sleeveless summer essentials.'],
            ['name' => 'Long Sleeves', 'icon' => 'fa-shirt', 'description' => 'Layer-ready long sleeve tees.'],
            ['name' => 'Accessories', 'icon' => 'fa-hat-cowboy', 'description' => 'Caps, bags and extras.'],
        ];

        foreach ($categories as $index => $data) {
            Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                    'sort_order' => $index + 1,
                    'status' => true,
                ]
            );
        }
    }
}
