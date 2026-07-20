<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendProductService;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly FrontendProductService $products,
    ) {}

    public function index(): View
    {
        // Cart lines are hydrated client-side from localStorage by main.js.
        return view('frontend.cart.index', [
            'cross' => $this->products->mappedActiveProducts(4)->all(),
            'colors' => $this->products->colors(),
        ]);
    }
}
