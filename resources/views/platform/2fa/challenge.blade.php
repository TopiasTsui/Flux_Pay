{{-- Minimal standalone 2FA challenge page. Not styled to match Orchid chrome on purpose
     — challenge is shown after login but before the user reaches the dashboard. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Two-Factor Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f7; margin: 0; padding: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.06); max-width: 380px; width: 100%; }
        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        p.help { color: #666; font-size: .9rem; margin: 0 0 1.5rem; }
        input[type="text"] { width: 100%; box-sizing: border-box; padding: .75rem; font-size: 1.5rem; letter-spacing: .3em; text-align: center; border: 1px solid #ddd; border-radius: 8px; }
        input[type="text"]:focus { outline: none; border-color: #3b82f6; }
        button { width: 100%; padding: .75rem; margin-top: 1rem; background: #1f2937; color: #fff; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #111827; }
        .error { background: #fee2e2; color: #991b1b; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
        .logout { display: block; text-align: center; margin-top: 1rem; color: #6b7280; font-size: .85rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Two-Factor Verification</h1>
        <p class="help">Enter the 6-digit code from your authenticator app.</p>

        @if (session('error') || $error ?? false)
            <div class="error">{{ session('error') ?: $error }}</div>
        @endif

        <form method="POST" action="{{ route('platform.2fa.verify') }}" autocomplete="off">
            @csrf
            <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" autofocus required>
            <button type="submit">Verify</button>
        </form>

        <a class="logout" href="{{ route('platform.logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Sign out
        </a>
        <form id="logout-form" method="POST" action="{{ route('platform.logout') }}" style="display:none">
            @csrf
        </form>
    </div>
</body>
</html>
