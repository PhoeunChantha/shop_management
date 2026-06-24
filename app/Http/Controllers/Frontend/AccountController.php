<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $orders = Catalog::orders();
        $active = collect($orders)->firstWhere('status', '!=', 'Delivered') ?? $orders[0];

        return view('frontend.account.dashboard', [
            'user'         => Catalog::user(),
            'orders'       => $orders,
            'active'       => $active,
            'products'     => collect(Catalog::products())->keyBy('id'),
            'notifUnread'  => collect(Catalog::notifications())->where('unread', true)->count(),
        ]);
    }

    public function profile(): View
    {
        return view('frontend.account.profile', ['user' => Catalog::user()]);
    }

    public function password(): View
    {
        return view('frontend.account.password', ['user' => Catalog::user()]);
    }

    public function addresses(): View
    {
        return view('frontend.account.addresses', [
            'user'      => Catalog::user(),
            'addresses' => Catalog::addresses(),
        ]);
    }

    public function notifications(): View
    {
        return view('frontend.account.notifications', [
            'user'          => Catalog::user(),
            'notifications' => Catalog::notifications(),
        ]);
    }

    public function wishlist(): View
    {
        return view('frontend.account.wishlist', [
            'user'     => Catalog::user(),
            'products' => Catalog::products(),
            'colors'   => Catalog::colors(),
        ]);
    }

    public function orders(): View
    {
        return view('frontend.account.orders', [
            'user'     => Catalog::user(),
            'orders'   => Catalog::orders(),
            'products' => collect(Catalog::products())->keyBy('id'),
        ]);
    }

    public function orderDetail(string $id): View
    {
        $order = Catalog::findOrder($id) ?? abort(404);

        return view('frontend.account.order-detail', [
            'user'     => Catalog::user(),
            'order'    => $order,
            'products' => collect(Catalog::products())->keyBy('id'),
            'colors'   => Catalog::colors(),
        ]);
    }

    public function orderTracking(string $id): View
    {
        $order = Catalog::findOrder($id) ?? abort(404);

        return view('frontend.account.order-tracking', [
            'user'  => Catalog::user(),
            'order' => $order,
        ]);
    }

    public function review(string $id, int $pid): View
    {
        $order = Catalog::findOrder($id) ?? abort(404);

        return view('frontend.account.review', [
            'user'    => Catalog::user(),
            'order'   => $order,
            'product' => Catalog::find($pid) ?? abort(404),
        ]);
    }
}
