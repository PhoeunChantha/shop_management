<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css" rel="stylesheet">
    @if(file_exists(public_path('assets/frontend/css/style.css')))
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/style.css') }}">
    @endif
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8f7f4; font-family: system-ui, sans-serif; padding: 2rem; }
        .err-code { font-size: clamp(80px, 18vw, 160px); font-weight: 800; color: #111827; line-height: 1; letter-spacing: -4px; margin: 0; }
        .err-title { font-size: clamp(22px, 4vw, 32px); font-weight: 700; color: #111827; margin: 12px 0 10px; }
        .err-msg { color: #6b7280; font-size: 16px; max-width: 420px; text-align: center; line-height: 1.6; margin-bottom: 36px; }
        .err-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
        .err-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; transition: opacity .15s; }
        .err-btn:hover { opacity: .85; text-decoration: none; }
        .err-btn-dark { background: #111827; color: #fff; }
        .err-btn-outline { background: transparent; color: #111827; border: 1.5px solid #d1d5db; }
        .err-divider { width: 40px; height: 3px; background: #111827; margin: 20px auto; border-radius: 2px; }
        .err-brand { font-size: 13px; color: #9ca3af; margin-top: 48px; }
    </style>
</head>
<body>
    <div style="text-align:center">
        <p class="err-code">@yield('code')</p>
        <div class="err-divider"></div>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-msg">@yield('message')</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="err-btn err-btn-dark">
                <i class="fa-solid fa-house" style="font-size:12px"></i> Back to store
            </a>
            @yield('extra_action')
        </div>
        <p class="err-brand">{{ config('app.name') }}</p>
    </div>
</body>
</html>
