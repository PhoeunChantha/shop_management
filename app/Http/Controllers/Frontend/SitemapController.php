<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Public XML sitemap: static pages + every active product detail page.
     */
    public function index(): Response
    {
        $urls = [];

        // Static, always-available pages.
        foreach (['home', 'shop.index', 'pages.about', 'pages.contact', 'pages.faq', 'pages.privacy', 'pages.terms'] as $name) {
            $urls[] = [
                'loc' => route('frontend.'.$name),
                'changefreq' => $name === 'home' || $name === 'shop.index' ? 'daily' : 'monthly',
                'priority' => $name === 'home' ? '1.0' : '0.7',
            ];
        }

        // Active products.
        Product::query()
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use (&$urls): void {
                if (blank($product->slug)) {
                    return;
                }

                $urls[] = [
                    'loc' => route('frontend.shop.show', $product->slug),
                    'lastmod' => $product->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        return response()
            ->view('frontend.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
