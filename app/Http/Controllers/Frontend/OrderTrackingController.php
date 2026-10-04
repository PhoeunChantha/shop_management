<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Frontend\OrderTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Order tracking for guests (no account): look an order up by its number +
 * email, or open the private link from the confirmation page / email.
 */
final class OrderTrackingController extends Controller
{
    public function __construct(
        private readonly OrderTrackingService $tracking,
    ) {}

    public function index(): View
    {
        return view('frontend.orders.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = $this->tracking->lookup($data['order_number'], $data['email']);

        if (! $order) {
            return back()->withInput()->withErrors([
                'order_number' => __('We could not find an order with that number and email. Please check both and try again.'),
            ]);
        }

        return redirect()->to($this->tracking->guestUrl($order));
    }

    /**
     * Reached only through a signed link (route middleware `signed`).
     */
    public function show(string $order): View
    {
        $model = Order::query()->with('details')->where('order_number', $order)->firstOrFail();

        return view('frontend.orders.track-show', [
            'order' => $model,
            'steps' => $this->tracking->steps($model),
            'statusLabel' => $this->tracking->statusLabel($model),
            'eta' => $this->tracking->estimatedDelivery($model),
        ]);
    }
}
