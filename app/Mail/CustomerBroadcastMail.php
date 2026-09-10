<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CustomerNotificationCampaign;
use App\Services\Admin\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerBroadcastMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CustomerNotificationCampaign $campaign,
        public readonly ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingService::class);
        $from = $settings->mailFrom();

        return new Envelope(
            from: $from['address'] ? new Address($from['address'], $from['name'] ?? $settings->siteName()) : null,
            subject: $this->campaign->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.customer-broadcast',
            with: [
                'campaign' => $this->campaign,
                'recipientName' => $this->recipientName,
                'storeName' => app(SettingService::class)->siteName(),
            ],
        );
    }
}
