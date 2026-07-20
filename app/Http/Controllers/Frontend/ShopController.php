<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\FrontendProductService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly FrontendProductService $products,
    ) {}

    public function index(Request $request): View
    {
        $products = $this->products->mappedActiveProducts();

        $categories = collect($products)
            ->groupBy('cat')
            ->map(fn ($categoryProducts) => [
                'count' => $categoryProducts->count(),
                'subcategories' => $categoryProducts->groupBy('subcat')->map->count()->all(),
            ])
            ->all();
        $brands = collect($products)->groupBy('brand')->map->count()->all();
        $prices = collect($products)->pluck('price')->filter();
        $minPrice = max(0, (int) floor((float) ($prices->min() ?: 0)));
        $maxPrice = max($minPrice, (int) ceil((float) ($prices->max() ?: 120)));

        return view('frontend.shop.index', [
            'products' => $products->all(),
            'categories' => $categories,
            'brands' => $brands,
            'sizes' => $this->products->sizes($products),
            'colors' => $this->products->colors(),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    public function show(string $product): View|RedirectResponse
    {
        $dynamicProduct = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('slug', $product)
            ->first();

        if (! $dynamicProduct && ctype_digit($product)) {
            $dynamicProduct = Product::query()
                ->with($this->products->relations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->find((int) $product);

            if ($dynamicProduct) {
                return redirect()->route('frontend.shop.show', $this->products->slug($dynamicProduct));
            }
        }

        if (! $dynamicProduct) {
            $dynamicProduct = Product::query()
                ->with($this->products->relations())
                ->withSum('variants', 'stock')
                ->where('status', 'active')
                ->get()
                ->first(fn (Product $item): bool => $this->products->slug($item) === $product);
        }

        if (! $dynamicProduct) {
            abort(404);
        }

        $product = $this->products->map($dynamicProduct);
        $related = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->where('id', '!=', $dynamicProduct->id)
            ->where('category_id', $dynamicProduct->category_id)
            ->orderBy('sort_order')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Product $product): array => $this->products->map($product))
            ->values()
            ->all();

        return view('frontend.shop.show', [
            'product' => $product,
            'related' => $related,
            'colors' => $product['color_map'] ?? $this->products->colors(),
            'reviews' => [],
        ]);
    }
}
