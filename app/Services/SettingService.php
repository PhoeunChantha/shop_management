<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SettingGroup;
use App\Helpers\ImageManager;
use App\Models\MediaAsset;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class SettingService
{
    /**
     * Admin theme colour keys mapped to their factory-default hex values.
     * Single source of truth for field defaults, reset, and global output.
     *
     * @var array<string, string>
     */
    private const THEME_DEFAULTS = [
        'admin_primary_color' => '#101928',
        'admin_secondary_color' => '#526178',
        'admin_success_color' => '#0f766e',
        'admin_warning_color' => '#c9a227',
        'admin_danger_color' => '#dc2626',
        'admin_info_color' => '#2563eb',
        'admin_light_color' => '#eef2f7',
        'admin_dark_color' => '#101827',
        'admin_sidebar_bg_color' => '#09111f',
        'admin_sidebar_panel_color' => '#121c2f',
        'admin_sidebar_text_color' => '#d8e2f0',
        'admin_sidebar_muted_color' => '#8fa1bb',
        'admin_sidebar_active_color' => '#14b8a6',
    ];

    /**
     * Human labels + helper text for each semantic colour, in display order.
     *
     * @var array<string, array{label: string, hint: string}>
     */
    private const THEME_LABELS = [
        'admin_primary_color' => ['label' => 'Primary', 'hint' => 'Save, New, Apply, primary actions'],
        'admin_secondary_color' => ['label' => 'Secondary', 'hint' => 'Cancel, Close, Back, Reset'],
        'admin_success_color' => ['label' => 'Success', 'hint' => 'Approve, Complete, Publish, Active'],
        'admin_warning_color' => ['label' => 'Warning', 'hint' => 'Edit, Update, Pending, Archive'],
        'admin_danger_color' => ['label' => 'Danger', 'hint' => 'Delete, Remove, Reject'],
        'admin_info_color' => ['label' => 'Info', 'hint' => 'View, Details, Preview, Export'],
        'admin_light_color' => ['label' => 'Light', 'hint' => 'Neutral surfaces, table headers'],
        'admin_dark_color' => ['label' => 'Dark', 'hint' => 'Emphasis / high-contrast accents'],
        'admin_sidebar_bg_color' => ['label' => 'Sidebar background', 'hint' => 'Main admin sidebar background'],
        'admin_sidebar_panel_color' => ['label' => 'Sidebar panel', 'hint' => 'Module cards, user panel, and brand panel'],
        'admin_sidebar_text_color' => ['label' => 'Sidebar text', 'hint' => 'Primary sidebar labels'],
        'admin_sidebar_muted_color' => ['label' => 'Sidebar muted text', 'hint' => 'Section captions and secondary labels'],
        'admin_sidebar_active_color' => ['label' => 'Sidebar active', 'hint' => 'Active module accent and selected link'],
    ];

    /**
     * Master list of languages the store can offer (code => label).
     * The admin picks a subset in Settings → Languages; everything that is
     * multilingual reads the chosen subset from here.
     *
     * @var array<string, string>
     */
    private const LANGUAGE_OPTIONS = [
        'en' => 'English (EN)',
        'km' => 'Khmer · ខ្មែរ (KM)',
        'zh' => 'Chinese · 中文 (ZH)',
        'th' => 'Thai · ไทย (TH)',
        'vi' => 'Vietnamese · Tiếng Việt (VI)',
        'fr' => 'French · Français (FR)',
        'es' => 'Spanish · Español (ES)',
        'ja' => 'Japanese · 日本語 (JA)',
        'ko' => 'Korean · 한국어 (KO)',
    ];

    /**
     * Starter checkout payment methods stored as one JSON settings value.
     *
     * @var array<int, array<string, mixed>>
     */
    private const DEFAULT_PAYMENT_METHODS = [
        [
            'id' => 'card',
            'name' => 'Card',
            'code' => 'card',
            'type' => 'online',
            'description' => 'Visa, Mastercard, and local bank cards.',
            'instructions' => 'Customer enters card number, expiry, and CVC at checkout.',
            'image' => '',
            'qr_image' => '',
            'bank_name' => '',
            'account_name' => '',
            'account_number' => '',
            'status' => true,
            'sort_order' => 1,
        ],
        [
            'id' => 'apple_pay',
            'name' => 'Apple Pay',
            'code' => 'apple_pay',
            'type' => 'online',
            'description' => 'Fast wallet checkout for Apple devices.',
            'instructions' => 'Show when the customer device supports Apple Pay.',
            'image' => '',
            'qr_image' => '',
            'bank_name' => '',
            'account_name' => '',
            'account_number' => '',
            'status' => true,
            'sort_order' => 2,
        ],
        [
            'id' => 'google_pay',
            'name' => 'Google Pay',
            'code' => 'google_pay',
            'type' => 'online',
            'description' => 'Fast wallet checkout for supported browsers.',
            'instructions' => 'Show when Google Pay is available for the customer.',
            'image' => '',
            'qr_image' => '',
            'bank_name' => '',
            'account_name' => '',
            'account_number' => '',
            'status' => true,
            'sort_order' => 3,
        ],
        [
            'id' => 'manual_qr',
            'name' => 'Manual QR Payment',
            'code' => 'manual_qr',
            'type' => 'manual',
            'description' => 'Customer scans your QR code and sends payment proof.',
            'instructions' => 'Scan the QR code, complete the transfer, then keep the receipt for confirmation.',
            'image' => '',
            'qr_image' => '',
            'bank_name' => '',
            'account_name' => '',
            'account_number' => '',
            'status' => false,
            'sort_order' => 4,
        ],
    ];

    /**
     * Fixed field definitions, keyed by the group they belong to.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    private function fieldDefinitions(): array
    {
        return [
            SettingGroup::General->value => [
                'site_name' => ['label' => 'Site name', 'type' => 'text', 'placeholder' => 'T-Shirt Shop', 'rules' => 'nullable|string|max:255'],
                'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'placeholder' => 'Wear your story', 'rules' => 'nullable|string|max:255'],
                'site_description' => ['label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Short description of your store', 'rules' => 'nullable|string|max:1000'],
                'site_copyright' => ['label' => 'Copyright text', 'type' => 'text', 'placeholder' => '© 2026 T-Shirt Shop', 'rules' => 'nullable|string|max:255'],
                'site_logo' => ['label' => 'Logo', 'type' => 'image', 'folder' => 'settings', 'accept' => 'image/png,image/jpeg,image/svg+xml,image/webp', 'help' => 'PNG, JPG, SVG or WebP — up to 2MB', 'rules' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048'],
                'site_favicon' => ['label' => 'Favicon', 'type' => 'image', 'folder' => 'settings', 'accept' => 'image/png,image/x-icon,image/svg+xml', 'help' => 'ICO, PNG or SVG — square, up to 1MB', 'rules' => 'nullable|mimes:png,ico,svg,jpg,jpeg|max:1024'],
            ],
            SettingGroup::Prefix->value => [
                'order_prefix' => ['label' => 'Order number prefix', 'type' => 'text', 'placeholder' => 'UT-', 'help' => 'Prepended to every order number — e.g. UT-2026-000123', 'rules' => 'nullable|string|max:20'],
                'product_sku_prefix' => ['label' => 'Product SKU prefix', 'type' => 'text', 'placeholder' => 'PRD-', 'help' => 'Used when a single product’s SKU is left blank — e.g. PRD-A1B2C3D4', 'rules' => 'nullable|string|max:20'],
            ],
            SettingGroup::Contact->value => [
                'contact_email' => ['label' => 'Email address', 'type' => 'email', 'placeholder' => 'help@tshirtshop.com', 'rules' => 'nullable|email|max:255'],
                'contact_email_note' => ['label' => 'Email note', 'type' => 'text', 'placeholder' => 'We reply within 24 hours', 'rules' => 'nullable|string|max:255'],
                'contact_phone' => ['label' => 'Phone number', 'type' => 'text', 'placeholder' => '+855 12 345 678', 'rules' => 'nullable|string|max:50'],
                'contact_hours' => ['label' => 'Support hours', 'type' => 'text', 'placeholder' => 'Mon–Fri, 9am–6pm ET', 'rules' => 'nullable|string|max:255'],
                'contact_store_name' => ['label' => 'Store name', 'type' => 'text', 'placeholder' => 'Flagship store', 'rules' => 'nullable|string|max:255'],
                'contact_address' => ['label' => 'Store address', 'type' => 'textarea', 'placeholder' => '211 Wythe Ave, Brooklyn, NY', 'rules' => 'nullable|string|max:500'],
                'contact_map_url' => ['label' => 'Map embed URL', 'type' => 'url', 'placeholder' => 'https://www.google.com/maps/embed?...', 'rules' => 'nullable|url|max:2000'],
            ],
            SettingGroup::Localization->value => [
                'languages' => [
                    'label' => 'Store languages',
                    'hint' => 'Pick the languages your store supports. Product content can then be entered per selected language.',
                    'type' => 'multiselect',
                    'options' => self::LANGUAGE_OPTIONS,
                    'default' => ['en'],
                ],
            ],
            SettingGroup::Appearance->value => $this->themeFieldDefinitions(),
        ];
    }

    /**
     * Selected store language codes (e.g. ['en','km','zh']), always non-empty.
     *
     * @return array<int, string>
     */
    public function languages(): array
    {
        $stored = json_decode((string) Setting::get('languages', '[]'), true);
        $stored = is_array($stored)
            ? array_values(array_filter($stored, fn ($code) => isset(self::LANGUAGE_OPTIONS[$code])))
            : [];

        return $stored ?: ['en'];
    }

    /**
     * Selected languages as code => label, in master-list order (for tabs).
     *
     * @return array<string, string>
     */
    public function activeLanguages(): array
    {
        $selected = $this->languages();

        return collect(self::LANGUAGE_OPTIONS)
            ->only($selected)
            ->all();
    }

    /**
     * The primary (default) language code — the first selected one.
     */
    public function primaryLanguage(): string
    {
        return $this->languages()[0] ?? 'en';
    }

    /**
     * Build the admin theme colour fields (all type "color") from the
     * default + label maps so the three stay in sync.
     *
     * @return array<string, array<string, string>>
     */
    private function themeFieldDefinitions(): array
    {
        $fields = [];

        foreach (self::THEME_DEFAULTS as $key => $default) {
            $fields[$key] = [
                'label' => self::THEME_LABELS[$key]['label'] ?? $key,
                'hint' => self::THEME_LABELS[$key]['hint'] ?? '',
                'type' => 'color',
                'default' => $default,
                'rules' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            ];
        }

        return $fields;
    }

    /**
     * Factory-default theme colours.
     *
     * @return array<string, string>
     */
    public function themeColorDefaults(): array
    {
        return self::THEME_DEFAULTS;
    }

    /**
     * Effective admin theme colours: saved values merged over defaults,
     * sanitised to valid 6-digit hex so they are safe to print into a
     * <style> block. Used to apply the theme globally.
     *
     * @return array<string, string>
     */
    public function themeColors(): array
    {
        $saved = Setting::map();
        $colors = [];

        foreach (self::THEME_DEFAULTS as $key => $default) {
            $value = $saved[$key] ?? null;
            $colors[$key] = (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value))
                ? $value
                : $default;
        }

        return $colors;
    }

    /**
     * The full schema that drives the tabs, fields and rendering.
     *
     * @return array<string, array<string, mixed>>
     */
    public function schema(): array
    {
        $definitions = $this->fieldDefinitions();
        $schema = [];

        foreach (SettingGroup::cases() as $group) {
            $entry = [
                'label' => $group->label(),
                'icon' => $group->icon(),
                'type' => $group->type(),
            ];

            if (! $group->isRepeater()) {
                $entry['fields'] = $definitions[$group->value] ?? [];
            }

            $schema[$group->value] = $entry;
        }

        return $schema;
    }

    /**
     * Validation rules derived from the field definitions + the social repeater.
     *
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        $rules = [
            'social_links' => ['nullable', 'array'],
            'social_links.*.icon' => ['nullable', 'string', 'max:60'],
            'social_links.*.title' => ['nullable', 'string', 'max:100'],
            'social_links.*.url' => ['nullable', 'url', 'max:255'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*.id' => ['nullable', 'string', 'max:80'],
            'payment_methods.*.name' => ['nullable', 'string', 'max:100'],
            'payment_methods.*.code' => ['nullable', 'string', 'max:80'],
            'payment_methods.*.type' => ['nullable', 'string', 'in:online,manual'],
            'payment_methods.*.description' => ['nullable', 'string', 'max:255'],
            'payment_methods.*.instructions' => ['nullable', 'string', 'max:1000'],
            'payment_methods.*.image' => ['nullable', 'string', 'max:255'],
            'payment_methods.*.qr_image' => ['nullable', 'string', 'max:255'],
            'payment_methods.*.bank_name' => ['nullable', 'string', 'max:100'],
            'payment_methods.*.account_name' => ['nullable', 'string', 'max:100'],
            'payment_methods.*.account_number' => ['nullable', 'string', 'max:100'],
            'payment_methods.*.status' => ['nullable', 'boolean'],
            'payment_methods.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'payment_method_images' => ['nullable', 'array'],
            'payment_method_images.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'payment_method_qr_images' => ['nullable', 'array'],
            'payment_method_qr_images.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ];

        foreach ($this->fieldDefinitions() as $fields) {
            foreach ($fields as $key => $field) {
                if (($field['type'] ?? '') === 'multiselect') {
                    $rules[$key] = ['nullable', 'array'];
                    $rules[$key.'.*'] = ['in:'.implode(',', array_keys($field['options'] ?? []))];

                    continue;
                }

                $rules[$key] = $field['rules'] ?? 'nullable|string|max:255';

                if (($field['type'] ?? '') === 'image') {
                    $rules[$key.'_media'] = ['nullable', 'string', 'max:255'];
                }
            }
        }

        return $rules;
    }

    /**
     * All saved settings as a key => value map.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Setting::map();
    }

    /**
     * Saved social links decoded from JSON.
     *
     * @return array<int, array<string, string>>
     */
    public function socialLinks(): array
    {
        return json_decode(Setting::get('social_links', '[]'), true) ?: [];
    }

    /**
     * Saved payment methods decoded from the JSON settings value.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentMethods(): array
    {
        $stored = Setting::get('payment_methods');
        $decoded = json_decode((string) $stored, true);
        $methods = is_array($decoded) ? $decoded : self::DEFAULT_PAYMENT_METHODS;

        return collect($methods)
            ->map(fn (array $method, int $index): array => $this->normalizePaymentMethod($method, $index))
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * Public URL of the uploaded site logo, or null when none is set.
     */
    public function logoUrl(): ?string
    {
        return ImageManager::url(Setting::get('site_logo'), 'settings');
    }

    /**
     * Public URL of the uploaded favicon, or null when none is set.
     */
    public function faviconUrl(): ?string
    {
        return ImageManager::url(Setting::get('site_favicon'), 'settings');
    }

    /**
     * Configured site name, falling back to the app name.
     */
    public function siteName(): string
    {
        $name = Setting::get('site_name');

        return filled($name) ? $name : (string) config('app.name', 'T-Shirt Shop');
    }

    /**
     * Configured order-number prefix (Settings → Prefix), defaulting to 'UT-'.
     */
    public function orderPrefix(): string
    {
        $prefix = Setting::get('order_prefix');

        return filled($prefix) ? trim($prefix) : 'UT-';
    }

    /**
     * Configured product-SKU prefix (Settings → Prefix), defaulting to 'PRD-'.
     * Used when auto-generating a SKU for a single product left blank.
     */
    public function productSkuPrefix(): string
    {
        $prefix = Setting::get('product_sku_prefix');

        return filled($prefix) ? trim($prefix) : 'PRD-';
    }

    /**
     * Persist all settings from the validated request payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public function save(array $validated): void
    {
        foreach ($this->fieldDefinitions() as $groupValue => $fields) {
            foreach ($fields as $key => $field) {
                // Image fields: only replace when a new file is uploaded, otherwise
                // keep the existing file. Stores the filename (ImageManager convention).
                if (($field['type'] ?? '') === 'image') {
                    $file = $validated[$key] ?? null;
                    $folder = $field['folder'] ?? 'settings';

                    if ($file instanceof UploadedFile) {
                        $newName = ImageManager::update($file, Setting::get($key), $folder);
                        Setting::set($key, $newName, $groupValue);
                    } elseif ($selected = $this->selectedMediaFilename($validated[$key.'_media'] ?? null, $folder)) {
                        Setting::set($key, $selected, $groupValue);
                    }

                    continue;
                }

                // Multiselect: store only the valid keys, as a JSON array.
                if (($field['type'] ?? '') === 'multiselect') {
                    $selected = array_values(array_intersect(
                        array_keys($field['options'] ?? []),
                        (array) ($validated[$key] ?? []),
                    ));
                    Setting::set($key, json_encode($selected), $groupValue);

                    continue;
                }

                Setting::set($key, $validated[$key] ?? null, $groupValue);
            }
        }

        $links = collect($validated['social_links'] ?? [])
            ->map(fn (array $row): array => [
                'icon' => $row['icon'] ?? '',
                'title' => $row['title'] ?? '',
                'url' => $row['url'] ?? '',
            ])
            ->filter(fn (array $row): bool => filled($row['url']) || filled($row['title']))
            ->values()
            ->all();

        Setting::set('social_links', json_encode($links), SettingGroup::Social->value);

        $this->savePaymentMethods(
            (array) ($validated['payment_methods'] ?? []),
            (array) ($validated['payment_method_images'] ?? []),
            (array) ($validated['payment_method_qr_images'] ?? []),
        );
    }

    /**
     * Search-friendly icon list for the Alpine picker: [{ c: class, k: keyword }].
     *
     * @return array<int, array<string, string>>
     */
    public function iconChoices(): array
    {
        return $this->socialIcons()
            ->map(fn (string $label, string $class): array => ['c' => $class, 'k' => strtolower($label)])
            ->values()
            ->all();
    }

    /**
     * Icon choices for the social link picker (FontAwesome class => label).
     *
     * @return Collection<string, string>
     */
    private function socialIcons(): Collection
    {
        return collect([
            'fa-brands fa-facebook' => 'Facebook',
            'fa-brands fa-facebook-messenger' => 'Messenger',
            'fa-brands fa-instagram' => 'Instagram',
            'fa-brands fa-x-twitter' => 'X (Twitter)',
            'fa-brands fa-twitter' => 'Twitter',
            'fa-brands fa-youtube' => 'YouTube',
            'fa-brands fa-tiktok' => 'TikTok',
            'fa-brands fa-linkedin' => 'LinkedIn',
            'fa-brands fa-telegram' => 'Telegram',
            'fa-brands fa-whatsapp' => 'WhatsApp',
            'fa-brands fa-pinterest' => 'Pinterest',
            'fa-brands fa-snapchat' => 'Snapchat',
            'fa-brands fa-reddit' => 'Reddit',
            'fa-brands fa-discord' => 'Discord',
            'fa-brands fa-twitch' => 'Twitch',
            'fa-brands fa-threads' => 'Threads',
            'fa-brands fa-github' => 'GitHub',
            'fa-brands fa-gitlab' => 'GitLab',
            'fa-brands fa-vimeo' => 'Vimeo',
            'fa-brands fa-behance' => 'Behance',
            'fa-brands fa-dribbble' => 'Dribbble',
            'fa-brands fa-medium' => 'Medium',
            'fa-brands fa-spotify' => 'Spotify',
            'fa-brands fa-soundcloud' => 'SoundCloud',
            'fa-brands fa-tumblr' => 'Tumblr',
            'fa-brands fa-flickr' => 'Flickr',
            'fa-brands fa-figma' => 'Figma',
            'fa-brands fa-google' => 'Google',
            'fa-brands fa-apple' => 'Apple',
            'fa-solid fa-globe' => 'Website',
            'fa-solid fa-envelope' => 'Email',
            'fa-solid fa-phone' => 'Phone',
            'fa-solid fa-location-dot' => 'Location',
            'fa-solid fa-link' => 'Other link',
        ]);
    }

    private function selectedMediaFilename(?string $filename, string $folder): ?string
    {
        $filename = trim((string) $filename);

        if ($filename === '') {
            return null;
        }

        return MediaAsset::query()
            ->where('folder', $folder)
            ->where('filename', $filename)
            ->value('filename');
    }

    /**
     * @param  array<int, array<string, mixed>>  $methods
     * @param  array<int|string, UploadedFile|null>  $images
     * @param  array<int|string, UploadedFile|null>  $qrImages
     */
    private function savePaymentMethods(array $methods, array $images, array $qrImages): void
    {
        $normalized = collect($methods)
            ->map(function (array $method, int|string $index) use ($images, $qrImages): array {
                $method = $this->normalizePaymentMethod($method, (int) $index);
                $uploaded = $images[$index] ?? null;
                $uploadedQr = $qrImages[$index] ?? null;

                if ($uploaded instanceof UploadedFile) {
                    $method['image'] = ImageManager::update($uploaded, $method['image'] ?: null, 'settings');
                }

                if ($uploadedQr instanceof UploadedFile) {
                    $method['qr_image'] = ImageManager::update($uploadedQr, $method['qr_image'] ?: null, 'settings');
                }

                return $method;
            })
            ->filter(fn (array $method): bool => filled($method['name']) || filled($method['code']))
            ->sortBy('sort_order')
            ->values()
            ->all();

        Setting::set('payment_methods', json_encode($normalized), SettingGroup::Payment->value);
    }

    /**
     * @param  array<string, mixed>  $method
     * @return array<string, mixed>
     */
    private function normalizePaymentMethod(array $method, int $index): array
    {
        $name = trim((string) ($method['name'] ?? ''));
        $code = trim((string) ($method['code'] ?? ''));

        return [
            'id' => trim((string) ($method['id'] ?? '')) ?: ($code ?: 'payment_'.($index + 1)),
            'name' => $name,
            'code' => str($code ?: $name)->slug('_')->toString(),
            'type' => in_array($method['type'] ?? null, ['online', 'manual'], true) ? $method['type'] : 'online',
            'description' => trim((string) ($method['description'] ?? '')),
            'instructions' => trim((string) ($method['instructions'] ?? '')),
            'image' => trim((string) ($method['image'] ?? '')),
            'qr_image' => trim((string) ($method['qr_image'] ?? '')),
            'bank_name' => trim((string) ($method['bank_name'] ?? '')),
            'account_name' => trim((string) ($method['account_name'] ?? '')),
            'account_number' => trim((string) ($method['account_number'] ?? '')),
            'status' => filter_var($method['status'] ?? false, FILTER_VALIDATE_BOOL),
            'sort_order' => (int) ($method['sort_order'] ?? ($index + 1)),
        ];
    }
}
