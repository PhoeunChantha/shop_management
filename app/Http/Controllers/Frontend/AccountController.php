<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
    ) {}

    public function dashboard(): View
    {
        $orders = $this->account->orders();
        $active = collect($orders)->firstWhere('status', '!=', 'Delivered') ?? ($orders[0] ?? null);

        return view('frontend.account.dashboard', [
            'user' => $this->account->user(),
            'orders' => $orders,
            'active' => $active,
            'products' => $this->account->productsById(),
            'notifUnread' => $this->account->unreadNotifications(),
        ]);
    }

    public function profile(): View
    {
        return view('frontend.account.profile', ['user' => $this->account->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $this->account->updateProfile($request->user(), $data);

        return back()->with('success', 'Profile updated.');
    }

    public function password(): View
    {
        return view('frontend.account.password', ['user' => $this->account->user()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('success', 'Password updated.');
    }

    public function addresses(): View
    {
        return view('frontend.account.addresses', [
            'user' => $this->account->user(),
            'addresses' => $this->account->addresses(),
        ]);
    }

    public function notifications(): View
    {
        return view('frontend.account.notifications', [
            'user' => $this->account->user(),
            'notifications' => $this->account->notifications(),
        ]);
    }

    public function wishlist(): View
    {
        return view('frontend.account.wishlist', [
            'user' => $this->account->user(),
            'products' => $this->account->wishlistProducts(),
            'colors' => $this->account->colors(),
        ]);
    }

    public function orders(): View
    {
        return view('frontend.account.orders', [
            'user' => $this->account->user(),
            'orders' => $this->account->orders(),
            'products' => $this->account->productsById(),
        ]);
    }

    public function orderDetail(string $id): View
    {
        $order = $this->account->findOrder($id) ?? abort(404);

        return view('frontend.account.order-detail', [
            'user' => $this->account->user(),
            'order' => $order,
            'products' => $this->account->productsById(),
            'colors' => $this->account->colors(),
        ]);
    }

    public function orderTracking(string $id): View
    {
        $order = $this->account->findOrder($id) ?? abort(404);

        return view('frontend.account.order-tracking', [
            'user' => $this->account->user(),
            'order' => $order,
        ]);
    }

    public function review(string $id, int $pid): View
    {
        $order = $this->account->findOrder($id) ?? abort(404);
        $product = $this->account->findProduct($pid) ?? abort(404);

        return view('frontend.account.review', [
            'user' => $this->account->user(),
            'order' => $order,
            'product' => $product,
        ]);
    }
}
