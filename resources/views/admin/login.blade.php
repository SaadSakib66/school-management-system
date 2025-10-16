<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EMS — Login</title>

  <!-- Poppins + Bootstrap -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (for eye toggle) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Page styles -->
  <link rel="stylesheet" href="{{ asset('front/css/styles.css') }}" />

  <!-- Minimal inline helpers (no .login-wrap background here) -->
  <style>
    .toggle-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);border:0;background:transparent;padding:0;line-height:1;cursor:pointer}
    .login-card{width:min(1100px,96%);box-shadow:0 10px 30px rgba(0,0,0,.06);border-radius:18px;overflow:hidden;background:#fff}
    .login-left{padding:40px 24px}
    .login-left .brand img{height:56px}
    .login-left .brand span{display:block;font-weight:600;margin-top:8px}
    .btn-ghost{display:inline-block;padding:.6rem 1.2rem;border-radius:999px;background:linear-gradient(90deg,#6a5acd,#00c2ff);color:#fff;text-decoration:none;font-weight:600;box-shadow:0 6px 16px rgba(0,0,0,.12)}
    .login-form{padding:40px 34px}
    .login-title{font-weight:700}
    .muted{color:#6c757d}
  </style>
</head>
<body>

  <main class="login-wrap">
    <div class="login-card row g-0 align-items-stretch">

      <!-- Left: animation + brand + DEMO button -->
      <div class="col-lg-6 position-relative login-left text-center">
        <div class="brand">
          <img src="{{ asset('front/images/logo.png') }}" alt="Logo">
          <span>Education Management System</span>
        </div>

        <!-- Local Lottie animation -->
        <lottie-player
          src="{{ asset('front/images/education.json') }}"
          background="transparent"
          speed="1"
          style="width:min(460px,86%);height:auto;margin-top:40px"
          loop autoplay>
        </lottie-player>

        <!-- Demo button (wire later if needed) -->
        <a href="#" class="btn-ghost mt-3">Try Demo</a>
      </div>

      <!-- Right: form -->
      <div class="col-lg-6 bg-white login-form">
        <div class="text-center mb-3 d-lg-none">
          <img src="{{ asset('front/images/logo.png') }}" alt="Logo" style="height:56px">
        </div>

        <h1 class="h4 login-title text-center">Welcome back</h1>
        <p class="text-center muted mb-4">Sign in to continue to your dashboard</p>

        @include('admin.message')

        <form action="{{ route('admin.login') }}" method="post" novalidate>
          @csrf

          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control @error('email') is-invalid @enderror"
              placeholder="Enter email"
              value="{{ old('email') }}"
              required
            >
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <!-- Password with eye inside input -->
          <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <div class="position-relative">
              <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Enter password"
                required
              >
              <button type="button" class="toggle-eye" id="togglePwd" aria-label="Show password" title="Show/Hide">
                <i id="eyeIcon" class="bi bi-eye-fill"></i>
              </button>
              @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ route('admin.forgotPassword') }}" class="link-primary text-decoration-none">Forgot password?</a>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">LOG IN</button>
        </form>

        <p class="text-center muted mt-4 mb-0">© <span id="year"></span> EMS</p>
      </div>
    </div>

    
    <a href="{{ route('landing') }}" class="btn-home" aria-label="Go to homepage">Home</a>

  </main>

  <!-- Lottie player -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <!-- Bootstrap bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Year
    document.getElementById('year').textContent = new Date().getFullYear();

    // Password eye toggle
    (function(){
      const pwd = document.getElementById('password');
      const btn = document.getElementById('togglePwd');
      const icon = document.getElementById('eyeIcon');
      if (!pwd || !btn || !icon) return;
      btn.addEventListener('click', function(){
        const isHidden = pwd.type === 'password';
        pwd.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('bi-eye-fill', !isHidden);
        icon.classList.toggle('bi-eye-slash-fill', isHidden);
      });
    })();
  </script>
</body>
</html>
