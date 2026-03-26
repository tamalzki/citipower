<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Citipower Electronics Supply</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Subtle background glow */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            left: -20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(245,171,0,.12) 0%, transparent 65%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(37,99,235,.1) 0%, transparent 65%);
            pointer-events: none;
        }

        .login-card {
            background: #111827;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            box-shadow: 0 32px 80px rgba(0,0,0,.6), 0 0 0 1px rgba(245,171,0,.06);
            width: 100%;
            max-width: 420px;
            padding: 44px 40px 40px;
            position: relative;
            z-index: 1;
        }

        /* Logo */
        .logo-wrap {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-wrap img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            filter: drop-shadow(0 4px 20px rgba(245,171,0,.3));
        }

        /* Heading */
        .login-heading {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-heading h1 {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -.3px;
        }
        .login-heading p {
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Alerts */
        .alert-error {
            background: rgba(220,38,38,.12);
            border: 1px solid rgba(220,38,38,.3);
            color: #fca5a5;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-status {
            background: rgba(22,163,74,.12);
            border: 1px solid rgba(22,163,74,.3);
            color: #86efac;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* Form */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 7px;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: #1e293b;
            border: 1.5px solid #334155;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            color: #e2e8f0;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input[type="email"]::placeholder,
        input[type="password"]::placeholder { color: #475569; }
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #f5ab00;
            box-shadow: 0 0 0 3px rgba(245,171,0,.15);
        }
        input[type="email"].is-error,
        input[type="password"].is-error {
            border-color: #dc2626;
        }

        .field-error {
            font-size: 12px;
            color: #f87171;
            margin-top: 5px;
        }

        /* Remember + Forgot */
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
        }
        .remember-label input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: #f5ab00;
            cursor: pointer;
        }
        .remember-label span {
            font-size: 13px;
            color: #64748b;
            text-transform: none;
            font-weight: 400;
            letter-spacing: 0;
        }
        .forgot-link {
            font-size: 13px;
            color: #f5ab00;
            text-decoration: none;
            transition: opacity .15s;
        }
        .forgot-link:hover { opacity: .75; }

        /* Submit */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #f5ab00 0%, #e09500 100%);
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            color: #0a0e1a;
            letter-spacing: .2px;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(245,171,0,.35);
            transition: opacity .15s, transform .1s, box-shadow .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover {
            opacity: .92;
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(245,171,0,.45);
        }
        .btn-login:active { transform: translateY(0); }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #334155;
            line-height: 1.7;
        }
        .login-footer span { color: #f5ab00; }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.06);
            margin: 28px 0;
        }
    </style>
</head>
<body>

<div class="login-card">

    {{-- Logo --}}
    <div class="logo-wrap">
        <img src="{{ asset('logo.png') }}" alt="Citipower Electronics Supply">
    </div>

    {{-- Heading --}}
    <div class="login-heading">
        <h1>Welcome back</h1>
        <p>Sign in to your account to continue</p>
    </div>

    {{-- Session status --}}
    @if(session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   class="{{ $errors->has('email') ? 'is-error' : '' }}"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••"
                   class="{{ $errors->has('password') ? 'is-error' : '' }}"
                   required autocomplete="current-password">
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="remember-row">
            <label class="remember-label">
                <input type="checkbox" name="remember" id="remember_me">
                <span>Keep me signed in</span>
            </label>
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-login">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Sign In
        </button>
    </form>

    <hr class="divider">

    <div class="login-footer">
        <span>Citipower Electronics Supply</span><br>
        Palma Gil St., Davao City
        <div style="margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,.05);
                    font-size:11px; color:#1e293b; letter-spacing:.3px;">
            Powered by <span style="color:#475569; font-weight:600;">Trinity Software</span>
        </div>
    </div>

</div>

</body>
</html>
