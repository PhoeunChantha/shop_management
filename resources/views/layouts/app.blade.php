<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light antialiased" style="height: 100vh; overflow: hidden;">

    <div class="d-flex vh-100 w-100 overflow-hidden">

        @include('layouts.sidebar')

        <div class="d-flex flex-column flex-grow-1 h-100 overflow-auto bg-light">

            @isset($header)
            <header class="bg-white border-bottom shadow-sm sticky-top">
                @include('layouts.header')
            </header>
            @endisset

            <main class="flex-grow-1 p-4">
                {{ $slot }}
            </main>

            <footer class="bg-white border-top py-3 px-4 d-flex justify-content-between text-secondary small">
                @include('layouts.footer')
            </footer>
        </div>
    </div>
</body>
</html>