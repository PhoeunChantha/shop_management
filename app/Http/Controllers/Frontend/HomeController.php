<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $products = Catalog::products();

        return view('frontend.home', [
            'best'    => array_slice(array_values(array_filter($products, fn ($p) => $p['badge'] === 'Best Seller' || $p['reviews'] > 200)), 0, 4),
            'fresh'   => array_slice(array_values(array_filter($products, fn ($p) => $p['tag'] === 'new')), 0, 4),
            'trend'   => array_slice($products, 4, 4),
            'flash'   => array_slice(array_values(array_filter($products, fn ($p) => $p['was'])), 0, 4),
            'collections' => Catalog::collections(),
            'reviews' => Catalog::reviews(),
            'marquee' => Catalog::marquee(),
        ]);
    }
}
