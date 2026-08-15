<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\AbandonedCart;
use App\Services\Admin\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AbandonedCart $cart,
    ) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingService::class);
        $from = $settings->mailFrom();

        return new Envelope(
            from: $from['address'] ? new Address($from['address'], $from['name'] ?? $settings->siteName()) : null,
            subject: 'You left something behind — '.$settings->siteName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.abandoned.reminder',
            with: [
                'cart' => $this->cart->loadMissing('items'),
                'storeName' => app(SettingService::class)->siteName(),
                'url' => route('frontend.cart.index'),
            ],
        );
    }
}
