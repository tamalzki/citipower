<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Citipower Electronics Supply</title>
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
        body::before {
            content: '';
            position: fixed;
            top: -30%; left: -20%;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(245,171,0,.12) 0%, transparent 65%);
            pointer-events: none;
        }
        .login-card {
            background: #111827;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            box-shadow: 0 32px 80px rgba(0,0,0,.6);
            width: 100%; max-width: 420px;
            padding: 44px 40px 40px;
            position: relative; z-index: 1;
        }
        .logo-wrap { text-align: center; margin-bottom: 28px; }
        .logo-wrap img { width: 90px; height: 90px; object-fit: contain; filter: drop-shadow(0 4px 20px rgba(245,171,0,.3)); }
        .login-heading { text-align: center; margin-bottom: 28px; }
        .login-heading h1 { font-size: 22px; font-weight: 700; color: #f1f5f9; }
        .login-heading p { font-size: 13px; color: #64748b; margin-top: 6px; line-height: 1.6; }
        .alert-status { background: rgba(22,163,74,.12); border: 1px solid rgba(22,163,74,.3); color: #86efac; border-radius: 10px; padding: 11px 14px; font-size: 13px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12.5px; font-weight: 600; color: #94a3b8; margin-bottom: 7px; letter-spacing: .3px; text-transform: uppercase; }
        input[type="email"] { width: 100%; background: #1e293b; border: 1.5px solid #334155; border-radius: 10px; padding: 11px 14px; font-size: 14px; color: #e2e8f0; outline: none; transition: border-color .2s, box-shadow .2s; }
        input[type="email"]:focus { border-color: #f5ab00; box-shadow: 0 0 0 3px rgba(245,171,0,.15); }
        .field-error { font-size: 12px; color: #f87171; margin-top: 5px; }
        .btn-login { width: 100%; background: linear-gradient(135deg, #f5ab00 0%, #e09500 100%); border: none; border-radius: 10px; padding: 13px; font-size: 15px; font-weight: 700; color: #0a0e1a; cursor: pointer; box-shadow: 0 4px 18px rgba(245,171,0,.35); transition: opacity .15s, transform .1s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-login:hover { opacity: .92; transform: translateY(-1px); }
        .back-link { display: block; text-align: center; margin-top: 18px; font-size: 13px; color: #f5ab00; text-decoration: none; }
        .back-link:hover { opacity: .75; }
        .login-footer { text-align: center; margin-top: 28px; font-size: 12px; color: #334155; line-height: 1.7; border-top: 1px solid rgba(255,255,255,.06); padding-top: 24px; }
        .login-footer span { color: #f5ab00; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo-wrap">
        <img src="{{ asset('logo.png') }}" alt="Citipower">
    </div>
    <div class="login-heading">
        <h1>Reset Password</h1>
        <p>Enter your email and we'll send you a link to reset your password.</p>
    </div>

    @if(session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="you@example.com" required autofocus>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn-login">Send Reset Link</button>
    </form>

    <a href="{{ route('login') }}" class="back-link">← Back to Sign In</a>

    <div class="login-footer">
        <span>Citipower Electronics Supply</span><br>Palma Gil St., Davao City
    </div>
</div>
</body>
</html>
