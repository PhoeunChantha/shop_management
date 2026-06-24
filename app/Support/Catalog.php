<?php

namespace App\Support;

/**
 * T Shirt Store — sample catalog / content provider.
 *
 * In a production app these arrays would come from Eloquent models
 * (Product, Collection, Order, …). This static provider keeps the
 * Blade frontend self-contained for the design hand-off. Swap the
 * method bodies for `Product::all()` etc. when wiring real data.
 */
class Catalog
{
    public static function colors(): array
    {
        return [
            'black' => ['name' => 'Black',   'hex' => '#1a1a1d'],
            'white' => ['name' => 'White',   'hex' => '#f4f4f2'],
            'stone' => ['name' => 'Stone',   'hex' => '#cbc2b4'],
            'olive' => ['name' => 'Olive',   'hex' => '#5c6149'],
            'blue' => ['name' => 'Cobalt',  'hex' => '#2f56b0'],
            'rust' => ['name' => 'Rust',    'hex' => '#b4552d'],
            'grey' => ['name' => 'Heather', 'hex' => '#9aa1ab'],
            'sand' => ['name' => 'Sand',    'hex' => '#d8c4a0'],
        ];
    }

    public static function sizes(): array
    {
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    }

    public static function marquee(): array
    {
        return ['Free shipping over $75', 'Easy 30-day returns', '240gsm organic cotton', 'Carbon-neutral delivery', 'Member early access'];
    }

    private static function tint(string $a, string $b): string
    {
        return "linear-gradient(150deg, $a, $b)";
    }

    public static function products(): array
    {
        $desc = 'Heavyweight 240gsm combed organic cotton with a structured boxy fit and a soft brushed interior. Garment-dyed for a lived-in tone that holds through wash after wash.';
        $sizes = self::sizes();
        $t = fn ($a, $b) => self::tint($a, $b);

        $rows = [
            ['Atlas Boxy Tee', 48, 64, $t('#e7e9ee', '#cfd4dd'), 'Tees', 'sale', ['black', 'stone', 'olive'], 4.8, 312, 'Best Seller', false, 'T-Shirt Shop', 'Boxy'],
            ['Mono Oversized Tee', 52, null, $t('#1f2024', '#33353c'), 'Tees', 'new', ['black', 'grey', 'white'], 4.9, 188, null, true, 'Urban Standard', 'Oversized'],
            ['Static Graphic Tee', 56, 70, $t('#ede6dc', '#d8c9b4'), 'Graphic', 'sale', ['sand', 'black', 'rust'], 4.7, 241, 'Trending', false, 'Core Supply', 'Typography'],
            ['Pace Performance Tee', 44, null, $t('#dfe7ee', '#bcccdb'), 'Active', null, ['blue', 'black', 'grey'], 4.6, 96, null, false, 'Motion Dept.', 'Performance'],
            ['Heritage Pocket Tee', 46, null, $t('#e8e4dd', '#cbbfa9'), 'Tees', null, ['stone', 'olive', 'black'], 4.5, 134, null, false, 'T-Shirt Shop', 'Essential'],
            ['Null Longsleeve', 64, 78, $t('#26282d', '#3b3e46'), 'Longsleeve', 'sale', ['black', 'olive'], 4.8, 77, 'Hot', true, 'Urban Standard', 'Heavyweight'],
            ['Drift Acid Wash Tee', 58, null, $t('#e3e8ec', '#c2cdd6'), 'Graphic', 'new', ['grey', 'blue'], 4.7, 145, null, false, 'Core Supply', 'Vintage'],
            ['Core Crew 3-Pack', 96, 120, $t('#eceae6', '#d6d2c8'), 'Tees', 'sale', ['white', 'black', 'stone'], 4.9, 503, 'Value', false, 'T-Shirt Shop', 'Essential'],
            ['Vertex Boxy Tee', 50, null, $t('#e9e2d6', '#cdbfa4'), 'Tees', null, ['sand', 'rust', 'olive'], 4.6, 112, null, false, 'Urban Standard', 'Boxy'],
            ['Signal Graphic Tee', 54, null, $t('#dde3e9', '#b8c4d0'), 'Graphic', 'new', ['blue', 'black'], 4.8, 167, null, false, 'Core Supply', 'Street Art'],
            ['Base Layer Tee', 38, null, $t('#edeef0', '#d3d6db'), 'Active', null, ['white', 'grey', 'black'], 4.4, 88, null, false, 'Motion Dept.', 'Training'],
            ['Fade Henley', 60, null, $t('#e6e0d6', '#c9bba2'), 'Longsleeve', null, ['stone', 'olive', 'black'], 4.7, 64, null, false, 'T-Shirt Shop', 'Layering'],
        ];

        $out = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'id' => $i + 1, 'name' => $r[0], 'price' => $r[1], 'was' => $r[2], 'tint' => $r[3],
                'cat' => $r[4], 'tag' => $r[5], 'colors' => $r[6], 'rating' => $r[7], 'reviews' => $r[8],
                'badge' => $r[9], 'dark' => $r[10], 'brand' => $r[11], 'subcat' => $r[12], 'sizes' => $sizes,
                'desc' => $desc, 'gallery' => 4,
            ];
        }

        return $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::products() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }

    public static function collections(): array
    {
        return [
            ['name' => 'Heavyweight', 'count' => 24, 'tint' => self::tint('#2a2c31', '#43454d'), 'dark' => true, 'sub' => '240–280gsm cotton'],
            ['name' => 'Graphic', 'count' => 31, 'tint' => self::tint('#e7ddcf', '#cdb79a'), 'dark' => false, 'sub' => 'Limited prints'],
            ['name' => 'Essentials', 'count' => 18, 'tint' => self::tint('#e6e9ee', '#cdd4de'), 'dark' => false, 'sub' => 'Everyday staples'],
            ['name' => 'Active', 'count' => 12, 'tint' => self::tint('#dde6ee', '#b9cad9'), 'dark' => false, 'sub' => 'Train & move'],
        ];
    }

    public static function reviews(): array
    {
        return [
            ['name' => 'Maya R.', 'city' => 'Brooklyn, NY', 'rating' => 5, 'text' => 'The weight on these tees is unreal. Boxy fit is exactly what I wanted — drapes perfectly, no shrink after three washes.', 'verified' => true],
            ['name' => 'Devon K.', 'city' => 'Austin, TX', 'rating' => 5, 'text' => 'Ordered the 3-pack and basically stopped wearing everything else. Fast shipping, premium packaging.', 'verified' => true],
            ['name' => 'Priya S.', 'city' => 'London, UK', 'rating' => 4, 'text' => 'Love the garment-dyed tones. Sizing runs a touch large which I prefer for the oversized look.', 'verified' => true],
            ['name' => 'Leo M.', 'city' => 'Berlin, DE', 'rating' => 5, 'text' => 'Genuinely the best fitting tee I own. The fabric feels expensive without being heavy.', 'verified' => true],
        ];
    }

    public static function user(): array
    {
        return ['name' => 'Alex Rivera', 'first' => 'Alex', 'last' => 'Rivera', 'email' => 'alex@email.com', 'phone' => '+1 (415) 555-0142', 'tier' => 'Gold', 'points' => 1240, 'since' => '2024'];
    }

    public static function orders(): array
    {
        return [
            ['id' => '8842', 'date' => 'Jun 2, 2026', 'status' => 'Shipped', 'stage' => 3, 'total' => 207.36, 'courier' => 'UrbanExpress', 'tracking' => 'UT9F4-22817-EX', 'eta' => 'Jun 8 – Jun 10, 2026', 'address' => '123 Market St, Apt 4B, San Francisco, CA 94103', 'items' => [
                ['pid' => 1, 'name' => 'Atlas Boxy Tee', 'size' => 'M', 'color' => 'black', 'qty' => 2, 'price' => 48],
                ['pid' => 8, 'name' => 'Core Crew 3-Pack', 'size' => 'L', 'color' => 'white', 'qty' => 1, 'price' => 96],
            ]],
            ['id' => '8610', 'date' => 'May 18, 2026', 'status' => 'Delivered', 'stage' => 5, 'total' => 63.11, 'courier' => 'UrbanStandard', 'tracking' => 'UT9F4-21044-ST', 'eta' => 'Delivered May 22, 2026', 'address' => '123 Market St, Apt 4B, San Francisco, CA 94103', 'items' => [
                ['pid' => 2, 'name' => 'Mono Oversized Tee', 'size' => 'L', 'color' => 'black', 'qty' => 1, 'price' => 52],
            ]],
            ['id' => '8421', 'date' => 'Apr 30, 2026', 'status' => 'Delivered', 'stage' => 5, 'total' => 103.68, 'courier' => 'UrbanStandard', 'tracking' => 'UT9F4-19320-ST', 'eta' => 'Delivered May 4, 2026', 'address' => '88 Valencia St, San Francisco, CA 94110', 'items' => [
                ['pid' => 9, 'name' => 'Vertex Boxy Tee', 'size' => 'M', 'color' => 'sand', 'qty' => 1, 'price' => 50],
                ['pid' => 5, 'name' => 'Heritage Pocket Tee', 'size' => 'M', 'color' => 'stone', 'qty' => 1, 'price' => 46],
            ]],
        ];
    }

    public static function findOrder(string $id): ?array
    {
        foreach (self::orders() as $o) {
            if ($o['id'] === $id) {
                return $o;
            }
        }

        return null;
    }

    public static function addresses(): array
    {
        return [
            ['id' => 1, 'label' => 'Home', 'default' => true, 'name' => 'Alex Rivera', 'line' => '123 Market St, Apt 4B', 'city' => 'San Francisco, CA 94103', 'country' => 'United States', 'phone' => '+1 (415) 555-0142'],
            ['id' => 2, 'label' => 'Work', 'default' => false, 'name' => 'Alex Rivera', 'line' => '500 Howard St, Floor 12', 'city' => 'San Francisco, CA 94105', 'country' => 'United States', 'phone' => '+1 (415) 555-0142'],
        ];
    }

    public static function notifications(): array
    {
        return [
            ['icon' => 'truck', 'type' => 'order', 'title' => 'Your order is on the way', 'body' => 'Order #UT-8842 shipped via UrbanExpress. Arriving Jun 8–10.', 'time' => '2h ago', 'unread' => true],
            ['icon' => 'tag', 'type' => 'promo', 'title' => 'Members-only: 20% off heavyweight', 'body' => 'Your early-access window is open for 48 hours.', 'time' => '1d ago', 'unread' => true],
            ['icon' => 'star', 'type' => 'review', 'title' => 'How was your Mono Oversized Tee?', 'body' => 'Leave a review and earn 50 thread points.', 'time' => '3d ago', 'unread' => false],
            ['icon' => 'check', 'type' => 'order', 'title' => 'Order delivered', 'body' => 'Order #UT-8610 was delivered. Hope you love it!', 'time' => '2w ago', 'unread' => false],
        ];
    }

    public static function faq(): array
    {
        return [
            ['cat' => 'Orders & Shipping', 'q' => 'How long does delivery take?', 'a' => 'Standard shipping is 2–4 business days (free over $75). Express is 1–2 business days. You’ll get a tracking link the moment your order ships.'],
            ['cat' => 'Orders & Shipping', 'q' => 'Do you ship internationally?', 'a' => 'Yes — we ship to 40+ countries. Duties and taxes are calculated at checkout so there are no surprises on delivery.'],
            ['cat' => 'Returns', 'q' => 'What is your return policy?', 'a' => 'Free 30-day returns on any unworn item with tags attached. Start a return from your order detail page and we’ll email a prepaid label.'],
            ['cat' => 'Returns', 'q' => 'When will I get my refund?', 'a' => 'Refunds are issued to your original payment method within 3–5 business days of us receiving your return.'],
            ['cat' => 'Product & Sizing', 'q' => 'How does the boxy fit run?', 'a' => 'Our heavyweight tees run true-to-size with an intentionally boxy, structured cut. Size down for a more fitted look. See the size guide on each product.'],
            ['cat' => 'Product & Sizing', 'q' => 'How should I wash my tees?', 'a' => 'Machine wash cold, tumble dry low. Garment-dyed pieces are pre-shrunk, but cold washing keeps the color richest.'],
            ['cat' => 'Account', 'q' => 'How do thread points work?', 'a' => 'Earn 1 point per $1 spent, plus bonus points for reviews and referrals. Redeem points for discounts at checkout.'],
        ];
    }
}
