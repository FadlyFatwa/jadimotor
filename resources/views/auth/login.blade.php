<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | JadiMotor</title>

  <link rel="icon" href="{{ asset('LOGO JDM BW.jpg') }}">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="{{ asset('templates/plugins/fontawesome-free/css/all.min.css') }}">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      overflow: hidden;
      position: relative;
    }

    /* ── Animated background ── */
    .bg-orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .35;
      animation: drift 12s ease-in-out infinite alternate;
      pointer-events: none;
    }
    .bg-orb-1 {
      width: 520px; height: 520px;
      background: radial-gradient(circle, #b8860b, transparent 70%);
      top: -120px; left: -100px;
    }
    .bg-orb-2 {
      width: 400px; height: 400px;
      background: radial-gradient(circle, #1e3a8a, transparent 70%);
      bottom: -80px; right: -80px;
      animation-delay: -6s;
    }
    .bg-orb-3 {
      width: 260px; height: 260px;
      background: radial-gradient(circle, #7c3aed, transparent 70%);
      bottom: 30%; left: 5%;
      animation-delay: -3s;
    }
    @keyframes drift {
      from { transform: translate(0, 0) scale(1); }
      to   { transform: translate(30px, 20px) scale(1.08); }
    }

    /* ── Card ── */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 16px;
    }

    .login-card {
      background: rgba(255, 255, 255, .05);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 20px;
      padding: 40px 36px 36px;
      box-shadow: 0 32px 64px rgba(0, 0, 0, .45);
    }

    /* ── Header ── */
    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }
    .login-logo {
      width: 72px;
      height: 72px;
      object-fit: contain;
      border-radius: 16px;
      background: rgba(255,255,255,.08);
      padding: 10px;
      margin-bottom: 16px;
    }
    .login-title {
      color: #f1f5f9;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: .4px;
      line-height: 1.2;
    }
    .login-subtitle {
      color: #94a3b8;
      font-size: .82rem;
      margin-top: 4px;
    }

    /* ── Error alert ── */
    .alert-login {
      background: rgba(239, 68, 68, .15);
      border: 1px solid rgba(239, 68, 68, .35);
      border-radius: 10px;
      color: #fca5a5;
      font-size: .83rem;
      padding: 10px 14px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ── Success alert ── */
    .alert-login-success {
      background: rgba(34, 197, 94, .15);
      border: 1px solid rgba(34, 197, 94, .35);
      border-radius: 10px;
      color: #86efac;
      font-size: .83rem;
      padding: 10px 14px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ── Form fields ── */
    .field-group {
      margin-bottom: 16px;
    }
    .field-label {
      display: block;
      color: #94a3b8;
      font-size: .75rem;
      font-weight: 500;
      letter-spacing: .6px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .field-wrap {
      position: relative;
    }
    .field-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: .85rem;
      pointer-events: none;
    }
    .field-input {
      width: 100%;
      background: rgba(255, 255, 255, .07);
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 10px;
      color: #f1f5f9;
      font-family: inherit;
      font-size: .9rem;
      padding: 11px 14px 11px 38px;
      outline: none;
      transition: border-color .2s, background .2s;
    }
    .field-input::placeholder { color: #475569; }
    .field-input:focus {
      border-color: #b8860b;
      background: rgba(255, 255, 255, .1);
    }
    .field-input.is-error { border-color: rgba(239, 68, 68, .6); }

    /* password toggle */
    .pwd-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #475569;
      cursor: pointer;
      font-size: .85rem;
      background: none;
      border: none;
      padding: 4px;
      transition: color .2s;
    }
    .pwd-toggle:hover { color: #94a3b8; }

    /* ── Remember me ── */
    .remember-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
    }
    .remember-row input[type="checkbox"] {
      width: 15px; height: 15px;
      accent-color: #b8860b;
      cursor: pointer;
    }
    .remember-row label {
      color: #94a3b8;
      font-size: .83rem;
      cursor: pointer;
      user-select: none;
    }

    /* ── Submit button ── */
    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, #b8860b 0%, #8b6508 100%);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-family: inherit;
      font-size: .92rem;
      font-weight: 600;
      letter-spacing: .3px;
      padding: 12px;
      cursor: pointer;
      transition: opacity .2s, transform .15s;
      position: relative;
      overflow: hidden;
    }
    .btn-login:hover { opacity: .9; transform: translateY(-1px); }
    .btn-login:active { transform: translateY(0); }
    .btn-login .btn-icon { margin-right: 6px; }

    /* ── Footer ── */
    .login-footer {
      text-align: center;
      color: #475569;
      font-size: .75rem;
      margin-top: 24px;
    }
  </style>
</head>

<body>

  <!-- Orbs -->
  <div class="bg-orb bg-orb-1"></div>
  <div class="bg-orb bg-orb-2"></div>
  <div class="bg-orb bg-orb-3"></div>

  <div class="login-wrapper">
    <div class="login-card">

      <!-- Header -->
      <div class="login-header">
        <img src="{{ asset('LOGO JDM BW.jpg') }}" class="login-logo" alt="JadiMotor">
        <div class="login-title">JADI MOTOR</div>
        <div class="login-subtitle">Sistem Informasi Manajemen</div>
      </div>

      <!-- Success alert -->
      @if(session('success'))
        <div class="alert-login-success">
          <i class="fas fa-check-circle"></i>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      <!-- Error alert -->
      @if($errors->any() || session('error'))
        <div class="alert-login">
          <i class="fas fa-exclamation-circle"></i>
          <span>
            {{ $errors->first() ?: session('error') }}
          </span>
        </div>
      @endif

      <!-- Form -->
      <form action="{{ route('login') }}" method="POST" autocomplete="off">
        @csrf

        <div class="field-group">
          <label class="field-label">Username</label>
          <div class="field-wrap">
            <i class="fas fa-user field-icon"></i>
            <input type="text"
                   name="username"
                   class="field-input {{ $errors->has('username') ? 'is-error' : '' }}"
                   placeholder="Masukkan username"
                   value="{{ old('username') }}"
                   required autofocus>
          </div>
        </div>

        <div class="field-group">
          <label class="field-label">Password</label>
          <div class="field-wrap">
            <i class="fas fa-lock field-icon"></i>
            <input type="password"
                   id="passwordInput"
                   name="password"
                   class="field-input {{ $errors->has('password') ? 'is-error' : '' }}"
                   placeholder="Masukkan password"
                   required>
            <button type="button" class="pwd-toggle" onclick="togglePassword()" tabindex="-1">
              <i class="fas fa-eye" id="pwdIcon"></i>
            </button>
          </div>
        </div>

        <div class="remember-row">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Ingat saya</label>
        </div>

        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt btn-icon"></i>Masuk
        </button>
      </form>

    </div>

    <div class="login-footer">
      &copy; {{ date('Y') }} JadiMotor. All rights reserved.
    </div>
  </div>

  <script>
    function togglePassword() {
      var input = document.getElementById('passwordInput');
      var icon  = document.getElementById('pwdIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>

</body>
</html>
