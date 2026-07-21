<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('frontend.pages.about', ['page' => $this->page('about')]);
    }

    public function contact(): View
    {
        return view('frontend.pages.contact', [
            'contact' => [
                'email' => Setting::get('contact_email'),
                'phone' => Setting::get('contact_phone'),
                'hours' => Setting::get('contact_hours'),
                'address' => Setting::get('contact_address'),
                'store_name' => Setting::get('contact_store_name'),
                'map_url' => Setting::get('contact_map_url'),
            ],
        ]);
    }

    public function faq(): View
    {
        $faq = Faq::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->latest()
            ->get(['question', 'answer', 'category'])
            ->map(fn (Faq $item): array => [
                'cat' => $item->category ?: 'General',
                'q' => $item->question,
                'a' => $item->answer,
            ])
            ->values()
            ->all();

        return view('frontend.pages.faq', ['faq' => $faq]);
    }

    public function privacy(): View
    {
        return view('frontend.pages.privacy', ['page' => $this->page('privacy')]);
    }

    public function terms(): View
    {
        return view('frontend.pages.terms', ['page' => $this->page('terms')]);
    }

    /**
     * Fetch a published admin-managed page by slug (null when none/unpublished).
     */
    private function page(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->where('status', true)->first();
    }
}

