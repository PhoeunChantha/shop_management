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

/**
 * CutLuy QR payments on the storefront (orders and wallet top-ups): opens the
 * QR popup on the host page and answers its status polling.
 */
class CutLuyController extends Controller
{
    public function __construct(private readonly CutLuyService $cutluy) {}

    // ---- Orders -------------------------------------------------------------

    /**
     * Open the QR popup for an order — over its confirmation page.
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        return redirect()->route('frontend.checkout.confirmation', ['pay' => 1])->with('order_id', $order->id);
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

    /**
     * Open the QR popup for a wallet top-up — over the wallet page.
     */
    public function topupPay(Request $request, WalletTopup $topup): RedirectResponse
    {
        $this->authorizeTopup($request, $topup);

        if ($topup->status === 'completed') {
            return redirect()->route('frontend.account.wallet')->with('success', __('Your wallet has been topped up.'));
        }

        return redirect()->route('frontend.account.wallet', ['pay' => $topup->tran_id]);
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

    // ---- Return from CutLuy's hosted checkout -------------------------------

    /**
     * Where CutLuy's hosted page sends the customer back (success and failure
     * alike), with ?status=&payment_id=&reference_id=. Those are only hints
     * that anyone can type: the payment is re-read from CutLuy, and the
     * visitor is only taken to an order / wallet that is theirs.
     */
    public function return(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_id' => ['nullable', 'string', 'max:120'],
            'reference_id' => ['nullable', 'string', 'max:120'],
        ]);

        $payment = filled($data['payment_id'] ?? null)
            ? CutluyPayment::where('payment_id', $data['payment_id'])->first()
            : (filled($data['reference_id'] ?? null) ? CutluyPayment::where('reference_id', $data['reference_id'])->latest('id')->first() : null);

        if (! $payment) {
            return redirect()->route('frontend.home');
        }

        $this->refresh($payment);
        $payable = $payment->payable;

        if ($payable instanceof Order) {
            $owns = (int) $request->session()->get('pending_order_id') === $payable->id
                || (Auth::check() && $payable->user_id === Auth::id());

            return $owns
                ? redirect()->route('frontend.checkout.confirmation', $payable->isPaid() ? [] : ['pay' => 1])->with('order_id', $payable->id)
                : redirect()->route('frontend.home');
        }

        if ($payable instanceof WalletTopup && Auth::id() === $payable->user_id) {
            return $payable->fresh()?->status === 'completed'
                ? redirect()->route('frontend.account.wallet')->with('success', __('Your wallet has been topped up.'))
                : redirect()->route('frontend.account.wallet', ['pay' => $payable->tran_id]);
        }

        // Someone else's (or a signed-out) top-up: sign in first, nothing revealed.
        return redirect()->route('frontend.account.wallet');
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

        $payment = $this->refresh($payment);

        // The QR ran out and CutLuy has not said "paid": offer a new one.
        $status = ! $payment->isPaid() && $payment->hasLapsed() ? 'expired' : $payment->status;

        return response()->json(['status' => $status]);
    }

    /**
     * Re-read an unsettled payment from CutLuy, at most once every 20 seconds.
     */
    private function refresh(CutluyPayment $payment): CutluyPayment
    {
        if (in_array($payment->status, CutluyPayment::OPEN, true) && Cache::add('cutluy:sync:'.$payment->id, 1, 20)) {
            try {
                return $this->cutluy->sync($payment);
            } catch (CutLuyException) {
                // Keep the last known status; the webhook will catch up.
            }
        }

        return $payment;
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
