<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'T-Shirt Shop — Premium Streetwear')</title>

    {{-- Motion gate: hide reveal targets before paint ONLY when motion is allowed.
         A failsafe reveals everything if main.js never clears it (e.g. CDN/JS failure). --}}
    <script>
        (function () {
            try {
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    document.documentElement.classList.add('ut-anim');
                    window.__utRevealFailsafe = window.setTimeout(function () {
                        document.documentElement.classList.add('ut-no-anim');
                    }, 4000);
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,500&family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- T-Shirt Shop design system (loads AFTER bootstrap to win specificity) --}}
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/style.css') }}?v={{ filemtime(public_path('assets/frontend/css/style.css')) }}">

    @stack('head')
</head>
<body>

@unless($bareLayout ?? false)
    <x-frontend.header />
@endunless

<main @class(['ut-page-pad-bottom' => ! ($bareLayout ?? false), 'ut-bare-main' => $bareLayout ?? false])>
    @yield('content')
</main>

@unless($bareLayout ?? false)
    <x-frontend.footer />
    <x-frontend.mobile-bottom-nav />
    <x-frontend.cart-drawer />
    <x-frontend.mobile-profile-drawer />
@endunless

{{-- Bootstrap bundle (Offcanvas, Modal, Collapse) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

{{-- Expose data + named routes to plain JS --}}
<script>
    window.UT_COLORS = @json(app(\App\Services\FrontendProductService::class)->colors());
    window.UT_URLS = {
        shop: "{{ route('frontend.shop.index') }}",
        cart: "{{ route('frontend.cart.index') }}",
        checkout: "{{ route('frontend.checkout.index') }}",
        confirm: "{{ route('frontend.checkout.confirmation') }}"
    };
</script>
<script src="{{ asset('assets/frontend/js/main.js') }}?v={{ filemtime(public_path('assets/frontend/js/main.js')) }}"></script>

@stack('scripts')
</body>
</html>
