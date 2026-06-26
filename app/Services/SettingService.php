<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SettingGroup;
use App\Models\Setting;
use Illuminate\Support\Collection;

final class SettingService
{
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
        ];
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
        ];

        foreach ($this->fieldDefinitions() as $fields) {
            foreach ($fields as $key => $field) {
                $rules[$key] = $field['rules'] ?? 'nullable|string|max:255';
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
     * Persist all settings from the validated request payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public function save(array $validated): void
    {
        foreach ($this->fieldDefinitions() as $groupValue => $fields) {
            foreach (array_keys($fields) as $key) {
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
}
