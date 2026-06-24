@php
    $title = 'Terms & Conditions';
    $updated = 'June 1, 2026';
    $sections = [
        ['h' => 'Acceptance of terms', 'p' => ['By accessing or purchasing from T-Shirt Shop, you agree to be bound by these Terms & Conditions and all applicable laws. If you do not agree, please do not use our services.']],
        ['h' => 'Account responsibilities', 'p' => ['You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorized use.']],
        ['h' => 'Orders & pricing', 'p' => ['All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order. Prices are shown in USD and may change without notice; the price at checkout is the price you pay.']],
        ['h' => 'Shipping & delivery', 'p' => ['Delivery estimates are provided in good faith but are not guaranteed. Risk of loss passes to you upon delivery to the carrier. Shipping fees are calculated at checkout.']],
        ['h' => 'Returns & refunds', 'p' => ['Unworn items with tags may be returned within 30 days for a full refund to the original payment method. Final-sale items are not eligible. See your order detail page to start a return.']],
        ['h' => 'Intellectual property', 'p' => ['All content, designs, logos, and graphics on this site are the property of T-Shirt Shop and may not be reproduced without written permission.']],
        ['h' => 'Limitation of liability', 'p' => ['To the maximum extent permitted by law, T-Shirt Shop is not liable for indirect, incidental, or consequential damages arising from the use of our products or services.']],
        ['h' => 'Governing law', 'p' => ['These terms are governed by the laws of the State of New York. Any disputes will be resolved in the courts located in New York County.']],
    ];
@endphp
@include('frontend.pages.partials.legal', compact('title', 'updated', 'sections'))

