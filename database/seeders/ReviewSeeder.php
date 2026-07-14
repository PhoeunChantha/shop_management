<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $bodies = [
            'Great quality and exactly as described. Would buy again!',
            'Fits perfectly and the material feels premium. Very happy.',
            'Shipping was fast but the colour is slightly different than the photo.',
            'Decent product for the price, though stitching could be better.',
            'Absolutely love it — my new favourite. Highly recommend.',
            'Comfortable and true to size. Five stars from me.',
            'Not bad, but I expected a bit more for the price.',
            'Excellent customer service and the product exceeded expectations.',
        ];
        $titles = ['Love it!', 'Great value', 'As described', 'Would recommend', 'Good quality', null];
        $statuses = ['approved', 'approved', 'approved', 'pending', 'pending', 'rejected'];

        Product::query()->inRandomOrder()->limit(8)->get()->each(function (Product $product) use ($bodies, $titles, $statuses) {
            foreach (range(1, random_int(2, 5)) as $ignored) {
                Review::create([
                    'product_id' => $product->id,
                    'user_id' => null,
                    'author_name' => fake()->name(),
                    'rating' => random_int(3, 5),
                    'title' => $titles[array_rand($titles)],
                    'body' => $bodies[array_rand($bodies)],
                    'status' => $statuses[array_rand($statuses)],
                    'is_verified' => (bool) random_int(0, 1),
                    'created_at' => now()->subDays(random_int(1, 60)),
                ]);
            }
        });
    }
}
