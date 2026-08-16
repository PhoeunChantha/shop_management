<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\WalletTopup;
use App\Services\Frontend\AccountService;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\InvoiceService;
use App\Services\Frontend\PaywayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
        private readonly CheckoutService $checkout,
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

        return back()->with('success', __('Profile updated.'));
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

        return back()->with('success', __('Password updated.'));
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

    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return back();
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('All notifications marked as read.'));
    }

    public function wallet(Request $request): View
    {
        $user = $request->user();

        // Enabled online + manual methods from Settings (wallet can't top up wallet).
        $methods = collect($this->checkout->paymentMethods())
            ->reject(fn (array $m): bool => ($m['type'] ?? '') === 'wallet')
            ->values()
            ->all();

        return view('frontend.account.wallet', [
            'user' => $this->account->user(),
            'balance' => (float) $user->wallet_balance,
            'transactions' => $user->walletTransactions()->with('order:id,order_number')->limit(40)->get(),
            'topupMethods' => $methods,
            'pendingTopups' => WalletTopup::where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('method_type', 'manual')
                ->latest()
                ->get(),
        ]);
    }

    public function walletTopup(Request $request): RedirectResponse
    {
        $methods = collect($this->checkout->paymentMethods())
            ->reject(fn (array $m): bool => ($m['type'] ?? '') === 'wallet');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'payment_method' => ['required', 'string', Rule::in($methods->pluck('code')->all())],
        ]);

        $method = $methods->firstWhere('code', $data['payment_method']);
        $isOnline = ($method['type'] ?? 'online') === 'online';

        if ($isOnline && ! app(PaywayService::class)->configured()) {
            return back()->with('error', __('Online top-up is not available right now.'));
        }

        $topup = WalletTopup::create([
            'user_id' => $request->user()->id,
            'tran_id' => 'WT'.now()->format('ymdHis').Str::upper(Str::random(4)),
            'payment_method' => $method['code'],
            'method_type' => $isOnline ? 'online' : 'manual',
            'amount' => round((float) $data['amount'], 2),
            'status' => 'pending',
        ]);

        // Online → gateway redirect. Manual → wait for admin approval before crediting.
        if ($isOnline) {
            return redirect()->route('frontend.payment.topup.pay', $topup);
        }

        return redirect()->route('frontend.account.wallet')->with('success', __('Top-up request submitted. Your balance will be credited once we confirm your payment.'));
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

    public function invoice(string $id, InvoiceService $invoices): Response
    {
        $order = $this->account->findOrderModel($id) ?? abort(404);

        return $invoices->pdf($order)->download($invoices->filename($order));
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

    public function storeReview(Request $request, string $id, int $pid): RedirectResponse
    {
        $order = $this->account->findOrder($id) ?? abort(404);
        abort_unless($this->account->orderContainsProduct($order, $pid), 404);

        if ($this->account->hasReviewed($request->user(), $pid)) {
            return redirect()
                ->route('frontend.account.orders')
                ->with('error', __('You have already reviewed this product.'));
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->account->submitReview($request->user(), $pid, $data);

        return redirect()
            ->route('frontend.account.orders')
            ->with('success', __('Thanks! Your review was submitted and is pending approval.'));
    }
}
