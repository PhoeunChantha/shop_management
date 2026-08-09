<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Admin\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Renders the order invoice PDF. Shared by the account download route and the
 * order-confirmation email so both produce an identical document.
 */
final class InvoiceService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * Build the dompdf document for an order (A4).
     */
    public function pdf(Order $order): DomPdf
    {
        $order->loadMissing('details');

        return Pdf::loadView('frontend.account.invoice-pdf', [
            'order' => $order,
            'store' => $this->storeInfo(),
        ])->setPaper('a4');
    }

    /**
     * Raw PDF bytes — for attaching to mail.
     */
    public function bytes(Order $order): string
    {
        return $this->pdf($order)->output();
    }

    public function filename(Order $order): string
    {
        return 'invoice-'.$order->order_number.'.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    private function storeInfo(): array
    {
        return [
            'name' => $this->settings->siteName(),
            'logo' => $this->settings->logoUrl(),
            'email' => Setting::get('contact_email'),
            'phone' => Setting::get('contact_phone'),
            'address' => Setting::get('contact_address'),
        ];
    }
}
