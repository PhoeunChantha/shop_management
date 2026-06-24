<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $products = Catalog::products();
        $categories = collect($products)
            ->groupBy('cat')
            ->map(fn ($categoryProducts) => [
                'count' => $categoryProducts->count(),
                'subcategories' => $categoryProducts->groupBy('subcat')->map->count()->all(),
            ])
            ->all();
        $brands = collect($products)->groupBy('brand')->map->count()->all();

        return view('frontend.shop.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'sizes' => Catalog::sizes(),
            'colors' => Catalog::colors(),
        ]);
    }

    public function show(int $id): View
    {
        $product = Catalog::find($id) ?? abort(404);
        $related = array_slice(array_values(array_filter(
            Catalog::products(),
            fn ($p) => $p['cat'] === $product['cat'] && $p['id'] !== $product['id']
        )), 0, 4);

        return view('frontend.shop.show', [
            'product' => $product,
            'related' => $related,
            'colors' => Catalog::colors(),
            'reviews' => Catalog::reviews(),
        ]);
    }
}
