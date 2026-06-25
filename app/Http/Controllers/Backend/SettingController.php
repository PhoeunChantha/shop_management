<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin|manager'),
        ];
    }

    /**
     * The settings schema: drives the tabs, fields, rendering and validation.
     */
    private function schema(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'icon' => 'fa-sliders',
                'fields' => [
                    'site_name' => ['label' => 'Site name', 'type' => 'text', 'placeholder' => 'T-Shirt Shop', 'rules' => 'nullable|string|max:255'],
                    'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'placeholder' => 'Wear your story', 'rules' => 'nullable|string|max:255'],
                    'site_description' => ['label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Short description of your store', 'rules' => 'nullable|string|max:1000'],
                    'site_copyright' => ['label' => 'Copyright text', 'type' => 'text', 'placeholder' => '© 2026 T-Shirt Shop', 'rules' => 'nullable|string|max:255'],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'icon' => 'fa-address-book',
                'fields' => [
                    'contact_email' => ['label' => 'Email address', 'type' => 'email', 'placeholder' => 'hello@example.com', 'rules' => 'nullable|email|max:255'],
                    'contact_phone' => ['label' => 'Phone number', 'type' => 'text', 'placeholder' => '+855 12 345 678', 'rules' => 'nullable|string|max:50'],
                    'contact_address' => ['label' => 'Address', 'type' => 'textarea', 'placeholder' => 'Street, city, country', 'rules' => 'nullable|string|max:500'],
                    'contact_hours' => ['label' => 'Support hours', 'type' => 'text', 'placeholder' => 'Mon–Fri, 9am–6pm', 'rules' => 'nullable|string|max:255'],
                ],
            ],
            'social' => [
                'label' => 'Social links',
                'icon' => 'fa-share-nodes',
                'fields' => [
                    'social_facebook' => ['label' => 'Facebook', 'type' => 'url', 'placeholder' => 'https://facebook.com/...', 'rules' => 'nullable|url|max:255'],
                    'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://instagram.com/...', 'rules' => 'nullable|url|max:255'],
                    'social_twitter' => ['label' => 'X (Twitter)', 'type' => 'url', 'placeholder' => 'https://x.com/...', 'rules' => 'nullable|url|max:255'],
                    'social_youtube' => ['label' => 'YouTube', 'type' => 'url', 'placeholder' => 'https://youtube.com/...', 'rules' => 'nullable|url|max:255'],
                    'social_tiktok' => ['label' => 'TikTok', 'type' => 'url', 'placeholder' => 'https://tiktok.com/@...', 'rules' => 'nullable|url|max:255'],
                ],
            ],
        ];
    }

    public function index(): View
    {
        return view('admin.settings.index', [
            'schema' => $this->schema(),
            'values' => Setting::map(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $schema = $this->schema();

        // Build validation rules from the schema.
        $rules = [];
        foreach ($schema as $group) {
            foreach ($group['fields'] as $key => $field) {
                $rules[$key] = $field['rules'] ?? 'nullable|string|max:255';
            }
        }

        $validated = $request->validate($rules);

        // Persist each field under its group.
        foreach ($schema as $groupKey => $group) {
            foreach ($group['fields'] as $key => $field) {
                Setting::set($key, $validated[$key] ?? null, $groupKey);
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully!');
    }
}
