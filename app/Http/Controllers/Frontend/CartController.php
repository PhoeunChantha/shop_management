<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\CartService;
use App\Services\Frontend\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CartService $cart,
    ) {}

    public function index(): View
    {
        // Cart lines are hydrated client-side from localStorage / the saved cart.
        return view('frontend.cart.index', [
            'cross' => $this->products->crossSell(4)->all(),
            'colors' => $this->products->colors(),
        ]);
    }

    /**
     * Persist the signed-in customer's cart server-side (cross-device) and
     * return the reconciled lines. Guests never reach this (auth-gated).
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.size' => ['nullable', 'string', 'max:60'],
            'items.*.color' => ['nullable', 'string', 'max:60'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'items' => $this->cart->sync($request->user(), $data['items'] ?? []),
        ]);
    }
}
