<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['name' => 'Extra Small', 'code' => 'XS'],
            ['name' => 'Small', 'code' => 'S'],
            ['name' => 'Medium', 'code' => 'M'],
            ['name' => 'Large', 'code' => 'L'],
            ['name' => 'Extra Large', 'code' => 'XL'],
            ['name' => 'Double Extra Large', 'code' => 'XXL'],
        ];

        foreach ($sizes as $index => $data) {
            Size::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'sort_order' => $index + 1,
                    'status' => true,
                ]
            );
        }
    }
}
