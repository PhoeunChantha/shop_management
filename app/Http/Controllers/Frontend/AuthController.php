<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ---- Page renderers ------------------------------------------------

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

    public function resetPassword(Request $request): View
    {
        return view('frontend.auth.reset-password', [
            'token' => (string) $request->route('token', $request->query('token', '')),
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function otp(): View
    {
        return view('frontend.auth.otp');
    }

    // ---- Actions -------------------------------------------------------

    /**
     * Authenticate a returning customer.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('frontend.account.dashboard'))
            ->with('success', 'Welcome back!');
    }

    /**
     * Register a new storefront customer and sign them in.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole('customer');

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('frontend.account.dashboard')
            ->with('success', 'Your account is ready.');
    }

    /**
     * Log the current customer out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('frontend.home')
            ->with('success', 'You have been signed out.');
    }

    /**
     * Email a password-reset link.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::ResetLinkSent
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }

    /**
     * Persist a new password from a valid reset token.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('frontend.login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }
}
