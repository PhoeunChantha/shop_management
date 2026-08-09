<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use App\Services\Admin\SettingService;
use App\Services\Frontend\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingService::class);
        $from = $settings->mailFrom();

        return new Envelope(
            from: $from['address'] ? new Address($from['address'], $from['name'] ?? $settings->siteName()) : null,
            subject: 'Order '.$this->order->order_number.' confirmed — '.$settings->siteName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmation',
            with: [
                'order' => $this->order->loadMissing('details'),
                'storeName' => app(SettingService::class)->siteName(),
                'url' => route('frontend.account.orders.show', $this->order->id),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $invoices = app(InvoiceService::class);

        return [
            Attachment::fromData(fn (): string => $invoices->bytes($this->order), $invoices->filename($this->order))
                ->withMime('application/pdf'),
        ];
    }
}
