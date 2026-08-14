<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // Built-in system pages (About/Privacy/Terms). Keyed by their stable
        // `page_key` so the storefront resolves them regardless of slug/title
        // edits. firstOrCreate preserves any content edited in admin.
        $pages = require database_path('data/system-pages.php');

        foreach ($pages as $key => $attributes) {
            Page::firstOrCreate(['page_key' => $key], $attributes + ['page_key' => $key, 'status' => true]);
        }

        $faqs = [
            ['question' => 'How long does delivery take?', 'answer' => 'Standard delivery takes 2–4 business days once your order is dispatched.', 'category' => 'Shipping', 'sort_order' => 1],
            ['question' => 'Do you ship internationally?', 'answer' => 'Yes — international shipping times and rates are shown at checkout.', 'category' => 'Shipping', 'sort_order' => 2],
            ['question' => 'How do I return an item?', 'answer' => 'You can return unworn items within 30 days for a full refund.', 'category' => 'Returns', 'sort_order' => 1],
            ['question' => 'What payment methods do you accept?', 'answer' => 'We accept major cards and cash on delivery where available.', 'category' => 'Orders', 'sort_order' => 1],
            ['question' => 'Can I change my order after placing it?', 'answer' => 'Contact us as soon as possible — we can amend orders before they are dispatched.', 'category' => 'Orders', 'sort_order' => 2],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq + ['status' => true]);
        }
    }
}
