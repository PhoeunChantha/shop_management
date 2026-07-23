@php
    $page = $page ?? null;
    $body = ($page && filled($page->content)) ? $page->content : null;
    $title = $body ? $page->title : 'Privacy Policy';
    $updated = $body ? ($page->updated_at?->format('F j, Y') ?? 'Recently') : 'June 1, 2026';
    $sections = [
        ['h' => 'Overview', 'p' => ['T-Shirt Shop ("we", "us") respects your privacy. This policy explains what information we collect, how we use it, and the choices you have. By using our store you agree to the practices described here.']],
        ['h' => 'Information we collect', 'p' => ['We collect information you provide directly — such as your name, email, shipping address, and payment details when you create an account or place an order.', 'We also automatically collect device, browser, and usage data through cookies and similar technologies to improve your experience.']],
        ['h' => 'How we use your information', 'p' => ['To process orders, deliver products, and provide customer support.', 'To send order updates, and — with your consent — marketing about drops and offers. You can opt out at any time.', 'To detect fraud, secure our services, and comply with legal obligations.']],
        ['h' => 'Sharing & disclosure', 'p' => ['We never sell your personal data. We share information only with service providers (payment, shipping, analytics) who process it on our behalf under strict confidentiality.']],
        ['h' => 'Your rights', 'p' => ['You may access, correct, export, or delete your personal data at any time from your account settings or by contacting us. Depending on your region, additional rights may apply under GDPR or CCPA.']],
        ['h' => 'Data retention & security', 'p' => ['We retain your data for as long as your account is active or as needed to provide services. All data is encrypted in transit and at rest using industry-standard protocols.']],
        ['h' => 'Contact us', 'p' => ['Questions about this policy? Email privacy@tshirtshop.com and our team will respond within 30 days.']],
    ];
@endphp
@include('frontend.pages.partials.legal', compact('title', 'updated', 'sections', 'body'))

