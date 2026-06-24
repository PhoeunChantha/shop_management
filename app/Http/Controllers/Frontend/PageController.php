<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
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
        return view('frontend.pages.faq', ['faq' => Catalog::faq()]);
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
