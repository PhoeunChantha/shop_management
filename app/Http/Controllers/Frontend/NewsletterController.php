<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Dedupe by email; record the first subscription time.
        NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['subscribed_at' => now()],
        );

        return back()->with('success', "You're in — check your inbox!");
    }
}
