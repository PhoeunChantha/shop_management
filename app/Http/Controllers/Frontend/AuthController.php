<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('frontend.auth.login');
    }

    public function register(): View
    {
        return view('frontend.auth.register');
    }

    public function forgotPassword(): View
    {
        return view('frontend.auth.forgot-password');
    }

    public function resetPassword(): View
    {
        return view('frontend.auth.reset-password');
    }

    public function otp(): View
    {
        return view('frontend.auth.otp');
    }
}
