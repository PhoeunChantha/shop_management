<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\CutLuyException;
use App\Http\Controllers\Controller;
use App\Models\CutluyPayment;
use App\Models\Order;
use App\Models\WalletTopup;
use App\Services\Frontend\CutLuyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * The storefront's own QR pay page for CutLuy: orders and wallet top-ups.
 */
class CutLuyController extends Controller
{
    public function __construct(private readonly CutLuyService $cutluy) {}

    // ---- Orders -------------------------------------------------------------

    public function pay(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $payment = $this->cutluy->paymentFor($order);
        } catch (CutLuyException $e) {
            return redirect()->route('frontend.checkout.confirmation')
                ->with('order_id', $order->id)
                ->with('error', $e->customerMessage());
        }

        if ($payment->isPaid()) {
            return $this->done($request, $order);
        }

        return view('frontend.payment.cutluy', [
            'payment' => $payment,
            'heading' => __('Scan to pay'),
            'reference' => __('Order').' #'.$order->order_number,
            'statusUrl' => route('frontend.payment.qr.status', $order),
            'renewUrl' => route('frontend.payment.qr.renew', $order),
            'doneUrl' => route('frontend.payment.qr.done', $order),
            'backUrl' => route('frontend.shop.index'),
            'backLabel' => __('Pay later — continue shopping'),
        ]);
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        return $this->statusFor($this->cutluy->latestFor($order));
    }

    public function renew(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        return redirect()->route('frontend.payment.qr', $order);
    }

    /**
     * Where the pay page lands once paid: the usual order confirmation.
     */
    public function done(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        return redirect()->route('frontend.checkout.confirmation')->with('order_id', $order->id);
    }

    // ---- Wallet top-ups -----------------------------------------------------

    public function topupPay(Request $request, WalletTopup $topup): View|RedirectResponse
    {
        $this->authorizeTopup($request, $topup);

        if ($topup->status === 'completed') {
            return redirect()->route('frontend.account.wallet')->with('success', __('Your wallet has been topped up.'));
        }

        try {
            $payment = $this->cutluy->paymentFor($topup);
        } catch (CutLuyException $e) {
            return redirect()->route('frontend.account.wallet')->with('error', $e->customerMessage());
        }

        return view('frontend.payment.cutluy', [
            'payment' => $payment,
            'heading' => __('Scan to top up your wallet'),
            'reference' => __('Top-up').' '.$topup->tran_id,
            'statusUrl' => route('frontend.payment.topup.qr.status', $topup),
            'renewUrl' => route('frontend.payment.topup.qr.renew', $topup),
            'doneUrl' => route('frontend.payment.topup.qr.done', $topup),
            'backUrl' => route('frontend.account.wallet'),
            'backLabel' => __('Back to wallet'),
        ]);
    }

    public function topupStatus(Request $request, WalletTopup $topup): JsonResponse
    {
        $this->authorizeTopup($request, $topup);

        return $this->statusFor($this->cutluy->latestFor($topup));
    }

    public function topupRenew(Request $request, WalletTopup $topup): RedirectResponse
    {
        $this->authorizeTopup($request, $topup);

        return redirect()->route('frontend.payment.topup.qr', $topup);
    }

    public function topupDone(Request $request, WalletTopup $topup): RedirectResponse
    {
        $this->authorizeTopup($request, $topup);

        return $topup->fresh()?->status === 'completed'
            ? redirect()->route('frontend.account.wallet')->with('success', __('Your wallet has been topped up.'))
            : redirect()->route('frontend.account.wallet')->with('info', __('We could not confirm your top-up yet. It will update once payment clears.'));
    }

    // ---- Shared -------------------------------------------------------------

    /**
     * What the pay page polls. Normally the webhook has already updated the
     * row; as a safety net (webhook delayed, or a local site CutLuy cannot
     * reach) an open payment is re-read from CutLuy at most once every 20s.
     */
    private function statusFor(?CutluyPayment $payment): JsonResponse
    {
        if (! $payment) {
            return response()->json(['status' => 'none']);
        }

        $unsettled = in_array($payment->status, CutluyPayment::OPEN, true);

        if ($unsettled && Cache::add('cutluy:sync:'.$payment->id, 1, 20)) {
            try {
                $payment = $this->cutluy->sync($payment);
            } catch (CutLuyException) {
                // Keep showing the last known status; the webhook will catch up.
            }
        }

        // The QR ran out and CutLuy has not said "paid": offer a new one.
        $status = ! $payment->isPaid() && $payment->hasLapsed() ? 'expired' : $payment->status;

        return response()->json(['status' => $status]);
    }

    /**
     * Only the order's owner (session pending order, or the signed-in customer)
     * may see or drive its payment.
     */
    private function authorizeOrder(Request $request, Order $order): void
    {
        $ownsViaSession = (int) $request->session()->get('pending_order_id') === $order->id;
        $ownsViaAccount = Auth::check() && $order->user_id === Auth::id();

        abort_unless($ownsViaSession || $ownsViaAccount, 403);
    }

    private function authorizeTopup(Request $request, WalletTopup $topup): void
    {
        abort_unless($topup->user_id === $request->user()->id && $topup->method_type === 'online', 403);
    }
}
