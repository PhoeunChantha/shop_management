<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * Send the visitor to the provider's consent screen.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (! $this->enabled($provider)) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Google sign-in is not available right now.');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback: match or create the customer, then log in.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if (! $this->enabled($provider)) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Google sign-in is not available right now.');
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Google sign-in was cancelled or failed. Please try again.');
        }

        $email = $oauthUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Your Google account did not share an email address.');
        }

        $user = User::firstWhere('email', $email);

        if (! $user) {
            $user = User::create([
                'name' => $oauthUser->getName() ?: ($oauthUser->getNickname() ?: 'Google User'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'avatar' => $this->storeAvatarFromUrl($oauthUser->getAvatar()),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole('customer');

            event(new Registered($user));
        } elseif (blank($user->avatar) && filled($oauthUser->getAvatar())) {
            // Backfill a profile photo for an existing account that has none.
            if ($avatar = $this->storeAvatarFromUrl($oauthUser->getAvatar())) {
                $user->forceFill(['avatar' => $avatar])->save();
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('frontend.home'))
            ->with('success', 'Signed in with '.ucfirst($provider).'.');
    }

    /**
     * A provider is usable only when toggled on and fully configured.
     */
    private function enabled(string $provider): bool
    {
        if ($provider !== 'google') {
            return false;
        }

        $toggledOn = (string) Setting::get('google_login', '1') === '1';

        return $toggledOn && $this->settings->googleConfigured();
    }

    /**
     * Download a remote avatar (e.g. the Google profile photo) into
     * public/uploads/users and return its stored path (matching the admin
     * avatar convention), or null on any failure. Never throws — a missing
     * photo must not break sign-in.
     */
    private function storeAvatarFromUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
                default => null,
            };

            // Ignore anything that is not a recognised image type.
            if ($extension === null) {
                return null;
            }

            $directory = 'uploads/users';
            File::ensureDirectoryExists(public_path($directory));

            $filename = 'google-'.Str::lower(Str::random(16)).'.'.$extension;
            File::put(public_path($directory.'/'.$filename), $response->body());

            return $directory.'/'.$filename;
        } catch (\Throwable) {
            return null;
        }
    }
}
