<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            'general' => [
                'site_name' => 'T-Shirt Shop',
                'site_tagline' => 'Wear your story',
                'site_description' => 'Premium tees, hoodies and everyday essentials.',
                'site_copyright' => '© '.date('Y').' T-Shirt Shop. All rights reserved.',
            ],
            // Contact
            'contact' => [
                'contact_email' => 'help@tshirtshop.com',
                'contact_email_note' => 'We reply within 24 hours',
                'contact_phone' => '+855 12 345 678',
                'contact_hours' => 'Mon–Fri, 9am–6pm ET',
                'contact_store_name' => 'Flagship store',
                'contact_address' => '211 Wythe Ave, Brooklyn, NY',
                'contact_map_url' => '',
            ],
            // Social links (stored as a JSON list)
            'social' => [
                'social_links' => json_encode([
                    ['icon' => 'fa-brands fa-facebook', 'title' => 'Facebook', 'url' => 'https://facebook.com/tshirtshop'],
                    ['icon' => 'fa-brands fa-instagram', 'title' => 'Instagram', 'url' => 'https://instagram.com/tshirtshop'],
                    ['icon' => 'fa-brands fa-tiktok', 'title' => 'TikTok', 'url' => 'https://tiktok.com/@tshirtshop'],
                ]),
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $value]
                );
            }
        }
    }
}
