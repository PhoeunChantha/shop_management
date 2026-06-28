<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Site branding + semantic theme colors (Settings). Resolved once and
         reused by the head, sidebar (via @include scope) and theme variables. --}}
    @php
        $brandingService = app(\App\Services\SettingService::class);
        $adminTheme = $brandingService->themeColors();
        $adminLogo = $brandingService->logoUrl();
        $adminFavicon = $brandingService->faviconUrl();
        $adminSiteName = $brandingService->siteName();

        // Tab title: "<Page> · <Site name>". Page is an explicit $title if a view
        // sets one, otherwise derived from the current route (admin.users.index → Users).
        $pageTitle = $title
            ?? \Illuminate\Support\Str::of((string) \Illuminate\Support\Facades\Route::currentRouteName())
                ->after('admin.')->before('.')->replace(['-', '_'], ' ')->headline()->value();
    @endphp

    <title>{{ filled($pageTitle) ? $pageTitle : $adminSiteName }}</title>

    {{-- Apply saved theme before paint to avoid a flash --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('admin-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    {{-- <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" /> --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($adminFavicon)
        <link rel="icon" href="{{ $adminFavicon }}">
    @endif

    <style id="admin-theme-vars">
        :root {
            --primary-color: {{ $adminTheme['admin_primary_color'] }};
            --secondary-color: {{ $adminTheme['admin_secondary_color'] }};
            --success-color: {{ $adminTheme['admin_success_color'] }};
            --warning-color: {{ $adminTheme['admin_warning_color'] }};
            --danger-color: {{ $adminTheme['admin_danger_color'] }};
            --info-color: {{ $adminTheme['admin_info_color'] }};
            --light-color: {{ $adminTheme['admin_light_color'] }};
            --dark-color: {{ $adminTheme['admin_dark_color'] }};
        }
    </style>
</head>

<body class="admin-shell antialiased" style="height: 100vh; overflow: hidden;">

    <div class="d-flex vh-100 w-100 overflow-hidden">

        @include('admin.layouts.sidebar')

        <div class="d-flex flex-column flex-grow-1 h-100 overflow-auto admin-workspace">

            @isset($header)
            <header class="admin-topbar sticky-top">
                @include('admin.layouts.header')
            </header>
            @endisset

            <main class="flex-grow-1 admin-main">
                {{ $slot }}
            </main>

            <footer class="admin-footer py-2 px-4 d-flex justify-content-between text-secondary small">
                @include('admin.layouts.footer')
            </footer>
        </div>
    </div>

    <x-toastr />

    @stack('js')
</body>
</html>
