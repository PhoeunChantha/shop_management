<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(): View
    {
        return view('frontend.checkout.index');
    }

    public function confirmation(): View
    {
        return view('frontend.checkout.confirmation', [
            'orderId' => random_int(8000, 9900),
        ]);
    }
}
