<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTopup;
use App\Services\Admin\WalletService;
use App\Services\Frontend\PaywayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaywayService $payway,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Build and auto-submit the PayWay purchase form for a pending order.
     */
    public function pay(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! $this->payway->configured()) {
            return redirect()->route('frontend.checkout.index')
                ->with('error', 'Online payment is not available right now.');
        }

        $order->loadMissing('details');
        $option = (string) (config('services.payway.options')[$order->payment_method] ?? '');

        $checkout = $this->payway->purchase(
            $order,
            $option,
            route('frontend.payment.callback'),
            route('frontend.payment.success', $order),
            route('frontend.payment.cancel', $order),
        );

        return view('frontend.payment.redirect', [
            'action' => $checkout['url'],
            'fields' => $checkout['fields'],
        ]);
    }

    /**
     * PayWay server-to-server pushback (CSRF-exempt). Confirms and marks paid.
     */
    public function callback(Request $request): Response
    {
        $tranId = (string) ($request->input('tran_id') ?: $request->input('return_params'));

        try {
            if ($order = Order::where('order_number', $tranId)->first()) {
                $this->payway->confirm($order);
            } elseif ($topup = WalletTopup::where('tran_id', $tranId)->first()) {
                $this->confirmTopup($topup);
            }
        } catch (\Throwable $e) {
            Log::error('PayWay callback failed: '.$e->getMessage(), ['tran_id' => $tranId]);
        }

        return response('OK');
    }

    /**
     * Auto-submit the PayWay form for a wallet top-up.
     */
    public function topupPay(Request $request, WalletTopup $topup): View|RedirectResponse
    {
        abort_unless($topup->user_id === $request->user()->id, 403);

        if (! $this->payway->configured()) {
            return redirect()->route('frontend.account.wallet')->with('error', 'Online top-up is not available right now.');
        }

        $checkout = $this->payway->purchaseTopup(
            $topup,
            $request->user(),
            route('frontend.payment.callback'),
            route('frontend.payment.topup.success', $topup),
            route('frontend.account.wallet'),
        );

        return view('frontend.payment.redirect', [
            'action' => $checkout['url'],
            'fields' => $checkout['fields'],
        ]);
    }

    public function topupSuccess(Request $request, WalletTopup $topup): RedirectResponse
    {
        abort_unless($topup->user_id === $request->user()->id, 403);

        try {
            $this->confirmTopup($topup);
        } catch (\Throwable $e) {
            Log::error('PayWay top-up confirm failed: '.$e->getMessage(), ['topup' => $topup->id]);
        }

        $message = $topup->fresh()?->status === 'completed'
            ? 'Your wallet has been topped up.'
            : 'We could not confirm your top-up yet. It will update once payment clears.';

        return redirect()->route('frontend.account.wallet')->with('success', $message);
    }

    /**
     * Verify a top-up and credit the wallet (idempotent, row-locked).
     */
    private function confirmTopup(WalletTopup $topup): void
    {
        $result = $this->payway->verifyTransaction($topup->tran_id);

        DB::transaction(function () use ($topup, $result): void {
            $locked = WalletTopup::whereKey($topup->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'completed') {
                return;
            }

            if ($result['approved']) {
                $locked->update(['status' => 'completed']);
                $this->wallet->credit($locked->user, (float) $locked->amount, 'topup', 'Wallet top-up '.$locked->tran_id);
            } else {
                $locked->update(['status' => 'failed']);
            }
        });
    }

    /**
     * Customer returns here after paying — confirm, then show the order.
     */
    public function success(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $this->payway->confirm($order);
        } catch (\Throwable $e) {
            Log::error('PayWay success confirm failed: '.$e->getMessage(), ['order' => $order->id]);
        }

        return redirect()->route('frontend.checkout.confirmation')->with('order_id', $order->id);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        return redirect()->route('frontend.checkout.index')
            ->with('error', 'Payment was cancelled. Your order is saved as unpaid.');
    }

    /**
     * Only the order's owner (session pending order, or the signed-in customer)
     * may drive its payment.
     */
    private function authorizeOrder(Request $request, Order $order): void
    {
        $ownsViaSession = (int) $request->session()->get('pending_order_id') === $order->id;
        $ownsViaAccount = Auth::check() && $order->user_id === Auth::id();

        abort_unless($ownsViaSession || $ownsViaAccount, 403);
    }
}
