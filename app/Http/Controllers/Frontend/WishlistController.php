<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Add or remove a single product from the customer's wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $user = $request->user();
        $changed = $user->wishlist()->toggle($data['product_id']);
        $wished = in_array($data['product_id'], $changed['attached'], true);

        return response()->json([
            'wished' => $wished,
            'count' => $user->wishlist()->count(),
        ]);
    }

    /**
     * Merge a guest's locally-stored wishlist into the account (on login),
     * without removing anything already saved. Returns the full id list.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $user = $request->user();

        if (! empty($data['ids'])) {
            $user->wishlist()->syncWithoutDetaching($data['ids']);
        }

        return response()->json([
            'ids' => $user->wishlist()->pluck('products.id')->all(),
            'count' => $user->wishlist()->count(),
        ]);
    }
}
