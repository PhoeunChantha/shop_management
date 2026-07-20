<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('frontend.pages.about');
    }

    public function contact(): View
    {
        return view('frontend.pages.contact');
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
        return view('frontend.pages.privacy');
    }

    public function terms(): View
    {
        return view('frontend.pages.terms');
    }
}
