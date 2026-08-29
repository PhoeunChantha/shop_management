<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * Whether reCAPTCHA is fully configured and enabled.
     */
    public function active(): bool
    {
        $cfg = $this->settings->recaptcha();

        return $cfg['enabled'] && filled($cfg['site_key']) && filled($cfg['secret_key']);
    }

    /**
     * The public site key to embed in Blade views.
     */
    public function siteKey(): string
    {
        return $this->settings->recaptcha()['site_key'];
    }

    /**
     * Verify a reCAPTCHA v3 token for a given action.
     * Returns true when the score meets the configured threshold.
     * Returns true (pass-through) when reCAPTCHA is disabled or misconfigured.
     */
    public function verify(string $token, string $action = 'submit'): bool
    {
        $cfg = $this->settings->recaptcha();

        if (! $cfg['enabled'] || blank($cfg['secret_key'])) {
            return true;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret'   => $cfg['secret_key'],
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                return false;
            }

            if (isset($data['action']) && $data['action'] !== $action) {
                return false;
            }

            return (float) ($data['score'] ?? 0) >= $cfg['min_score'];
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification failed', ['error' => $e->getMessage()]);

            return true;
        }
    }

    /**
     * Whether the register form should be protected.
     */
    public function protectsRegister(): bool
    {
        return $this->active() && $this->settings->recaptcha()['protect_register'];
    }

    /**
     * Whether the login form should be protected.
     */
    public function protectsLogin(): bool
    {
        return $this->active() && $this->settings->recaptcha()['protect_login'];
    }
}
