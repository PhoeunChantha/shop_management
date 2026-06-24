<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        // Cart lines are hydrated client-side from localStorage by main.js.
        return view('frontend.cart.index', [
            'cross'  => array_slice(Catalog::products(), 0, 4),
            'colors' => Catalog::colors(),
        ]);
    }
}
