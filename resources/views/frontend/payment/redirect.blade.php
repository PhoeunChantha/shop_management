<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting to secure payment…</title>
</head>
<body onload="document.getElementById('paywayForm').submit()"
      style="font-family:system-ui,sans-serif;text-align:center;padding:80px 20px;color:#1a1611">
    <div style="max-width:420px;margin:0 auto">
        <div style="width:56px;height:56px;border-radius:50%;border:3px solid #e5e0d5;border-top-color:#111;margin:0 auto 20px;animation:spin 1s linear infinite"></div>
        <h1 style="font-size:20px;margin:0 0 8px">Redirecting to secure payment…</h1>
        <p style="color:#6b6459;font-size:14px">Please wait while we take you to ABA PayWay to complete your payment.</p>

        <form id="paywayForm" method="POST" action="{{ $action }}">
            @foreach ($fields as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <noscript>
                <button type="submit" style="margin-top:16px;padding:12px 22px;border:0;border-radius:10px;background:#111;color:#fff;font-weight:600;cursor:pointer">
                    Continue to payment
                </button>
            </noscript>
        </form>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
