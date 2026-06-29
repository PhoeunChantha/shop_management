<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Black', 'code' => 'BLK', 'hex_code' => '#111827'],
            ['name' => 'White', 'code' => 'WHT', 'hex_code' => '#FFFFFF'],
            ['name' => 'Navy', 'code' => 'NVY', 'hex_code' => '#1E3A8A'],
            ['name' => 'Red', 'code' => 'RED', 'hex_code' => '#EF4444'],
            ['name' => 'Forest Green', 'code' => 'GRN', 'hex_code' => '#15803D'],
            ['name' => 'Beige', 'code' => 'BGE', 'hex_code' => '#E7DCC8'],
            ['name' => 'Charcoal', 'code' => 'CHR', 'hex_code' => '#374151'],
            ['name' => 'Sky Blue', 'code' => 'SKY', 'hex_code' => '#38BDF8'],
        ];

        foreach ($colors as $index => $data) {
            Color::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'hex_code' => $data['hex_code'],
                    'sort_order' => $index + 1,
                    'status' => true,
                ]
            );
        }
    }
}
