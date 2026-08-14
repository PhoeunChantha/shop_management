<?php

declare(strict_types=1);

/**
 * Canonical content for the built-in "system" storefront pages (About, Privacy,
 * Terms). Single source of truth shared by the migration (upgrades existing
 * installs in place) and ContentSeeder (fresh installs). Each is keyed by its
 * stable `page_key` — the storefront looks pages up by this key, not the
 * editable slug, so renaming a page's title in admin never breaks its URL.
 *
 * The HTML mirrors the storefront copy so the seeded pages read the same as the
 * original hardcoded designs, but are fully editable from Admin → Pages.
 */

return [
    'about' => [
        'title' => 'About Us',
        'slug' => 'about',
        'seo_title' => 'About — T-Shirt Shop',
        'seo_description' => 'Heavyweight essentials, done right. The story behind T-Shirt Shop and what we stand for.',
        'content' => <<<'HTML'
<h2>Heavyweight essentials, done right.</h2>
<p>T-Shirt Shop started with one frustration: every great tee either fell apart or cost a fortune. So we made our own.</p>
<h3>From a studio in Brooklyn to 50,000+ closets</h3>
<p>We obsess over the details most brands skip — the weight of the fabric, the drape of the shoulder, the way a collar holds up after a hundred washes. Every T-Shirt Shop piece is a small rebellion against disposable fashion.</p>
<p>Today we're a team of designers, makers, and wearers building the wardrobe staples we always wanted.</p>
<h3>What we stand for</h3>
<ul>
<li><strong>Built to last</strong> — Heavyweight 240gsm cotton, triple-stitched seams, and a fit engineered to hold its shape for years, not seasons.</li>
<li><strong>Made responsibly</strong> — Organic, GOTS-certified cotton and carbon-neutral delivery on every order. Premium without the planetary cost.</li>
<li><strong>Direct to you</strong> — No middlemen, no markups. We design in-studio and ship straight to your door at a fair price.</li>
</ul>
HTML,
    ],

    'privacy' => [
        'title' => 'Privacy Policy',
        'slug' => 'privacy',
        'seo_title' => 'Privacy Policy — T-Shirt Shop',
        'seo_description' => 'How T-Shirt Shop collects, uses, and protects your personal information.',
        'content' => <<<'HTML'
<h2>Overview</h2>
<p>T-Shirt Shop ("we", "us") respects your privacy. This policy explains what information we collect, how we use it, and the choices you have. By using our store you agree to the practices described here.</p>
<h2>Information we collect</h2>
<p>We collect information you provide directly — such as your name, email, shipping address, and payment details when you create an account or place an order.</p>
<p>We also automatically collect device, browser, and usage data through cookies and similar technologies to improve your experience.</p>
<h2>How we use your information</h2>
<ul>
<li>To process orders, deliver products, and provide customer support.</li>
<li>To send order updates, and — with your consent — marketing about drops and offers. You can opt out at any time.</li>
<li>To detect fraud, secure our services, and comply with legal obligations.</li>
</ul>
<h2>Sharing &amp; disclosure</h2>
<p>We never sell your personal data. We share information only with service providers (payment, shipping, analytics) who process it on our behalf under strict confidentiality.</p>
<h2>Your rights</h2>
<p>You may access, correct, export, or delete your personal data at any time from your account settings or by contacting us. Depending on your region, additional rights may apply under GDPR or CCPA.</p>
<h2>Data retention &amp; security</h2>
<p>We retain your data for as long as your account is active or as needed to provide services. All data is encrypted in transit and at rest using industry-standard protocols.</p>
<h2>Contact us</h2>
<p>Questions about this policy? Email privacy@tshirtshop.com and our team will respond within 30 days.</p>
HTML,
    ],

    'terms' => [
        'title' => 'Terms & Conditions',
        'slug' => 'terms',
        'seo_title' => 'Terms & Conditions — T-Shirt Shop',
        'seo_description' => 'The terms that govern the use of T-Shirt Shop and purchases made through our store.',
        'content' => <<<'HTML'
<h2>Acceptance of terms</h2>
<p>By accessing or purchasing from T-Shirt Shop, you agree to be bound by these Terms &amp; Conditions and all applicable laws. If you do not agree, please do not use our services.</p>
<h2>Account responsibilities</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorized use.</p>
<h2>Orders &amp; pricing</h2>
<p>All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order. Prices are shown in USD and may change without notice; the price at checkout is the price you pay.</p>
<h2>Shipping &amp; delivery</h2>
<p>Delivery estimates are provided in good faith but are not guaranteed. Risk of loss passes to you upon delivery to the carrier. Shipping fees are calculated at checkout.</p>
<h2>Returns &amp; refunds</h2>
<p>Unworn items with tags may be returned within 30 days for a full refund to the original payment method. Final-sale items are not eligible. See your order detail page to start a return.</p>
<h2>Intellectual property</h2>
<p>All content, designs, logos, and graphics on this site are the property of T-Shirt Shop and may not be reproduced without written permission.</p>
<h2>Limitation of liability</h2>
<p>To the maximum extent permitted by law, T-Shirt Shop is not liable for indirect, incidental, or consequential damages arising from the use of our products or services.</p>
<h2>Governing law</h2>
<p>These terms are governed by the laws of the State of New York. Any disputes will be resolved in the courts located in New York County.</p>
HTML,
    ],
];
