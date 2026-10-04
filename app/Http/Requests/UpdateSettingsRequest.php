<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Admin\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the SettingPolicy in the controller.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return app(SettingService::class)->validationRules();
    }

    /**
     * Accept bare R2 hosts ("media.example.com") and trailing slashes: add the
     * https:// scheme and trim so the stored value is always a clean base URL.
     */
    protected function prepareForValidation(): void
    {
        foreach (['r2_url', 'r2_endpoint'] as $key) {
            $value = trim((string) $this->input($key, ''));

            if ($value === '') {
                continue;
            }

            if (! preg_match('#^https?://#i', $value)) {
                $value = 'https://'.$value;
            }

            // The S3 endpoint is host-only: Cloudflare shows it with the bucket
            // appended, which would double the bucket in every request path.
            if ($key === 'r2_endpoint' && ($host = parse_url($value, PHP_URL_HOST))) {
                $value = parse_url($value, PHP_URL_SCHEME).'://'.$host;
            }

            $this->merge([$key => rtrim($value, '/')]);
        }
    }

    /**
     * Switching media storage to R2 needs a secret key: either one typed now or
     * one already saved (a blank password field means "keep the saved one").
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('media_disk') !== 'r2' || $this->filled('r2_secret_access_key')) {
                return;
            }

            if (blank(app(SettingService::class)->r2()['secret'])) {
                $validator->errors()->add('r2_secret_access_key', __('Enter the R2 secret access key to use R2 storage.'));
            }
        });
    }
}
