<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login &middot; {{ config('app.name', 'Boleto') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --accent: #0fb39a;
            --accent-2: #0a8f7c;
            --ink: #1f2329;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #0a0f0e;
            color: #e9ecf1;
            display: flex;
        }
        .login-wrap { display: flex; width: 100%; min-height: 100vh; }

        /* ---- Left brand panel ---- */
        .brand-panel {
            flex: 1 1 50%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            background: linear-gradient(135deg, #0a1f1b 0%, #0f3b33 38%, #0a8f7c 88%, #0fb39a 130%);
        }
        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 80% 20%, rgba(15,179,154,.40), transparent 45%),
                radial-gradient(circle at 15% 85%, rgba(20,120,90,.45), transparent 40%);
            animation: drift 14s ease-in-out infinite alternate;
        }
        @keyframes drift {
            from { transform: translate3d(0,0,0) scale(1); }
            to   { transform: translate3d(0,-18px,0) scale(1.08); }
        }
        .brand-panel > * { position: relative; z-index: 1; }
        .brand-logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 800; letter-spacing: .5px; }
        .brand-logo i { color: #fff; font-size: 1.8rem; }
        .brand-hero h1 { font-size: 2.7rem; line-height: 1.1; margin: 0 0 16px; font-weight: 800; }
        .brand-hero p { font-size: 1.05rem; color: rgba(255,255,255,.82); max-width: 420px; line-height: 1.6; }
        .brand-features { list-style: none; padding: 0; margin: 28px 0 0; display: grid; gap: 14px; }
        .brand-features li { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,.92); font-size: .98rem; }
        .brand-features i { background: rgba(255,255,255,.16); width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; font-size: 1rem; }
        .brand-foot { font-size: .82rem; color: rgba(255,255,255,.6); }

        /* ---- Right form panel ---- */
        .form-panel {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: #14171c;
        }
        .form-card { width: 100%; max-width: 400px; }
        .form-card .eyebrow { color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: .12em; font-size: .74rem; }
        .form-card h2 { font-size: 1.85rem; font-weight: 800; margin: 6px 0 4px; }
        .form-card .sub { color: #9aa3af; font-size: .95rem; margin-bottom: 28px; }

        .alert-err {
            background: rgba(255,80,70,.12);
            border: 1px solid rgba(255,80,70,.4);
            color: #ff9b94;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: .88rem;
            margin-bottom: 20px;
        }
        .alert-err div + div { margin-top: 4px; }

        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: .82rem; color: #b6bdc9; margin-bottom: 7px; font-weight: 600; }
        .input-shell { position: relative; }
        .input-shell i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 1.05rem; }
        .field input[type=email],
        .field input[type=password] {
            width: 100%;
            padding: 13px 14px 13px 44px;
            background: #1d2127;
            border: 1px solid #2c3138;
            border-radius: 11px;
            color: #f1f3f6;
            font-size: .95rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(15,179,154,.20);
        }
        .toggle-pass { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); left: auto; cursor: pointer; color: #6b7280; background: none; border: 0; }
        .row-between { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; font-size: .85rem; }
        .row-between label { display: flex; align-items: center; gap: 7px; color: #aab2bd; cursor: pointer; }
        .row-between a { color: var(--accent); text-decoration: none; }
        .row-between a:hover { text-decoration: underline; }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 11px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 800;
            color: #04201b;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 8px 22px rgba(15,179,154,.38);
            transition: transform .12s, box-shadow .12s;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(15,179,154,.5); }
        .btn-login:active { transform: translateY(0); }

        .back-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 26px; color: #8b94a1; font-size: .86rem; text-decoration: none; }
        .back-link:hover { color: #cfd4db; }

        @media (max-width: 860px) {
            .brand-panel { display: none; }
            .form-panel { flex-basis: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <!-- Brand panel -->
        <aside class="brand-panel">
            <div class="brand-logo">
                <i class="bi bi-ticket-perforated-fill"></i> {{ config('app.name', 'Boleto') }}
            </div>
            <div class="brand-hero">
                <h1>Admin<br>Control Center</h1>
                <p>Manage movies, showtimes, events, bookings and media — all from one secure dashboard.</p>
                <ul class="brand-features">
                    <li><i class="bi bi-film"></i> Movies, showtimes &amp; seat plans</li>
                    <li><i class="bi bi-bar-chart-line"></i> Bookings &amp; revenue at a glance</li>
                    <li><i class="bi bi-images"></i> Built-in media file manager</li>
                </ul>
            </div>
            <div class="brand-foot">&copy; {{ date('Y') }} {{ config('app.name', 'Boleto') }}. Restricted access — authorized staff only.</div>
        </aside>

        <!-- Form panel -->
        <main class="form-panel">
            <div class="form-card">
                <div class="eyebrow">Administrator</div>
                <h2>Welcome back</h2>
                <div class="sub">Sign in to access the admin dashboard.</div>

                @if ($errors->any())
                    <div class="alert-err">
                        @foreach ($errors->all() as $err)<div><i class="bi bi-exclamation-circle"></i> {{ $err }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email address</label>
                        <div class="input-shell">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-shell">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" id="password" placeholder="••••••••" required>
                            <button type="button" class="toggle-pass" aria-label="Show password" onclick="(function(b){var i=document.getElementById('password');var s=i.type==='password';i.type=s?'text':'password';b.firstElementChild.className=s?'bi bi-eye-slash':'bi bi-eye';})(this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="row-between">
                        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
                    </div>
                    <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Sign in to Dashboard</button>
                </form>

                <a href="{{ route('home') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to site</a>
            </div>
        </main>
    </div>
</body>
</html>
