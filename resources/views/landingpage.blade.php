<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>School Management Software</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --sidebar-dark:#343a40; --page-bg:#f4f6f9; --text-dark:#212529;
      --brand-blue:#0d6efd; --brand-green:#198754; --brand-yellow:#ffc107; --brand-red:#dc3545;
    }
    body{ background: var(--page-bg); color: var(--text-dark); }
    .navbar{ box-shadow: 0 2px 8px rgba(0,0,0,.06); background:#fff; }
    .hero-wrap{
      position:relative; color:#e9ecef; background:
      radial-gradient(1200px 500px at 10% 10%, rgba(13,110,253,.08), transparent 60%),
      radial-gradient(1200px 500px at 90% 20%, rgba(25,135,84,.08), transparent 60%),
      radial-gradient(1200px 500px at 50% 90%, rgba(220,53,69,.06), transparent 60%),
      #0f1220;
    }
    .hero-grid{ position:absolute; inset:0; opacity:.2; pointer-events:none;
      background-image:linear-gradient(rgba(255,255,255,.09) 1px, transparent 1px),
                       linear-gradient(90deg, rgba(255,255,255,.09) 1px, transparent 1px);
      background-size:24px 24px;
    }
    .hero-card{ background:#121826; border:1px solid rgba(255,255,255,.08);
      border-radius:1.25rem; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,.35);
    }
    .feature-card{ background:#fff; border:1px solid #e9ecef; border-radius:1rem; transition:.2s; }
    .feature-card:hover{ transform:translateY(-4px); box-shadow:0 14px 30px rgba(0,0,0,.06); }
    .module-pill{ background:#fff; border:1px solid #e9ecef; border-radius:.75rem; padding:.8rem 1rem;
      display:flex; align-items:center; justify-content:space-between;
    }
    .pricing-card{ background:#fff; border:1px solid #e9ecef; border-radius:1rem; box-shadow:0 10px 24px rgba(0,0,0,.04); }
    footer{ border-top:1px solid #e9ecef; background:#fff; }
    .btn-blue{ background:var(--brand-blue); border-color:var(--brand-blue); }
    .btn-blue:hover{ background:#0b5ed7; border-color:#0a58ca; }
    .btn-outline-blue{ border-color:rgba(13,110,253,.35); color:var(--brand-blue); }
    .btn-outline-blue:hover{ background:rgba(13,110,253,.08); }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('landing') }}">
      {{-- <img src="" class="rounded-circle" width="28" height="28" alt=""> --}}
      <strong>Barabd Education Management Software</strong>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="topnav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#modules">Modules</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
      </ul>
      <div class="d-flex ms-lg-3">
        <a href="{{ route('admin.login.page') }}" class="btn btn-outline-blue me-2">Sign in</a>
        <a href="{{ route('admin.login.page') }}" class="btn btn-blue text-white">Get Started</a>
      </div>
    </div>
  </div>
</nav>

<section class="hero-wrap py-5">
  <div class="hero-grid"></div>
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h1 class="display-5 fw-bold mt-2">Run your <span class="text-warning">School</span> with confidence.</h1>
        <p class="lead mt-3 text-white-50">
          All-in-one platform for classes, subjects, teachers, parents, exams, timetables,
          attendance, homework, marks, fees and more.
        </p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="{{ route('admin.login.page') }}" class="btn btn-blue btn-lg text-white"><i class="bi bi-box-arrow-in-right me-1 text-white"></i> Go to Portal</a>
          <a href="#features" class="btn btn-outline-light btn-lg"><i class="bi bi-search me-1"></i> Explore Features</a>
        </div>
        <p class="text-white-50 small mt-3">Role-based access: Admin • Teacher • Student • Parent</p>
      </div>

      <div class="col-lg-6">
        <div class="hero-card p-2">
          <img
            src="#"
            onerror="this.src='https://images.unsplash.com/photo-1553877522-43269d4ea984?q=80&w=1400&auto=format&fit=crop';"
            alt="Dashboard preview"
            class="img-fluid rounded-3">
        </div>
        <p class="text-center small text-white-50 mt-2 mb-0">Sample dashboard preview</p>
      </div>
    </div>
  </div>
</section>

<section id="features" class="py-6 py-lg-7" style="margin-top: 40px;">
  <div class="container">
    <h2 class="h3 fw-bold">Why schools choose us</h2>
    <p class="text-muted">Fast, secure and built on best practices.</p>

    <div class="row g-4 mt-1">
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-people"></i>
          </div>
          <h5>Role-based Access</h5>
          <p class="mb-0 text-muted">Separate dashboards for Admin, Teacher, Student & Parent.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-calendar-week"></i>
          </div>
          <h5>Attendance & Timetable</h5>
          <p class="mb-0 text-muted">Daily attendance, period schedules and class routines.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-clipboard2-check"></i>
          </div>
          <h5>Exams & Marks</h5>
          <p class="mb-0 text-muted">Create exams, enter marks, auto grade and share reports.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-megaphone"></i>
          </div>
          <h5>Homework & Notice</h5>
          <p class="mb-0 text-muted">Assign, submit, and notify guardians instantly.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-cash-coin"></i>
          </div>
          <h5>Fees & Accounting</h5>
          <p class="mb-0 text-muted">Invoice, collect fees, track dues and ledger summaries.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card p-4 h-100">
          <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-3 fs-5 p-2 mb-3">
            <i class="bi bi-graph-up"></i>
          </div>
          <h5>Reports & Analytics</h5>
          <p class="mb-0 text-muted">Student progress, teacher load, exam stats and more.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="modules" class="py-6 py-lg-7" style="margin-top: 40px;">
  <div class="container">
    <h2 class="h3 fw-bold">Core Modules</h2>
    <p class="text-muted">Everything your school runs on—centralized.</p>

    <div class="row g-3 mt-1">
      @foreach ([
        'Classes & Sections','Subjects','Students','Teachers',
        'Parents / Guardians','Class Timetable','Exams & Schedules','Marks Register',
        'Attendance','Homework','Communications','Calendar'
      ] as $m)
      <div class="col-12 col-md-6 col-lg-3">
        <div class="module-pill">
          <span>{{ $m }}</span>
          <i class="bi bi-chevron-right text-muted"></i>
        </div>
      </div>
      @endforeach
    </div>

    <div class="mt-4">
      <a href="{{ route('admin.login.page') }}" class="btn btn-blue btn-lg text-white">
        Enter Portal <i class="bi bi-arrow-right-short"></i>
      </a>
    </div>
  </div>
</section>

{{-- <section id="pricing" class="py-6 py-lg-7" style="margin-top: 40px;">
  <div class="container">
    <h2 class="h3 fw-bold">Simple pricing</h2>
    <p class="text-muted">Use as-is or customize for your institution.</p>

    <div class="row g-4 mt-1">
      <div class="col-md-6 col-lg-4">
        <div class="pricing-card p-4 h-100">
          <h5>Starter</h5>
          <div class="display-6 fw-bold text-primary mt-2">Free</div>
          <ul class="list-unstyled mt-3 mb-4">
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Unlimited users</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Core modules</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Community support</li>
          </ul>
          <a href="{{ route('admin.login.page') }}" class="btn btn-outline-primary w-100">Choose</a>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="pricing-card p-4 h-100 border-2 border-primary">
          <h5>Pro</h5>
          <div class="display-6 fw-bold text-primary mt-2">$49<span class="fs-5 fw-semibold">/mo</span></div>
          <ul class="list-unstyled mt-3 mb-4">
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Advanced reports</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Fees & accounting</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Priority support</li>
          </ul>
          <a href="{{ route('admin.login.page') }}" class="btn btn-blue w-100">Choose</a>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="pricing-card p-4 h-100">
          <h5>Enterprise</h5>
          <div class="display-6 fw-bold text-primary mt-2">Contact</div>
          <ul class="list-unstyled mt-3 mb-4">
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Custom modules</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>SLA & training</li>
            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Dedicated support</li>
          </ul>
          <a href="{{ route('admin.login.page') }}" class="btn btn-outline-primary w-100">Choose</a>
        </div>
      </div>
    </div>
  </div>
</section> --}}

<section id="faq" class="py-6 py-lg-7" style="margin: 40px;">
  <div class="container">
    <h2 class="h3 fw-bold">FAQ</h2>

    <div class="accordion mt-3" id="faqAcc">
      <div class="accordion-item">
        <h2 class="accordion-header" id="q1">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">
            Is this secure?
          </button>
        </h2>
        <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
          <div class="accordion-body">
            Built with Laravel best practices, CSRF protection, hashed passwords and role-based authorization.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q2">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">
            Can we customize modules?
          </button>
        </h2>
        <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
          <div class="accordion-body">
            Yes. Add fields, reports and workflows to match your school’s policies.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q3">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">
            Does it support guardians?
          </button>
        </h2>
        <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
          <div class="accordion-body">
            Parents can view attendance, homework, marks, notices and pay fees online.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q4">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4">
            How do we login?
          </button>
        </h2>
        <div id="a4" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
          <div class="accordion-body">
            Use the “Go to Portal” button above ({{ route('admin.login.page') }}) and point it to your live login page.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="py-4">
  <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
    <p class="mb-0 small">© {{ date('Y') }} All rights reserved by B.A.R & Associates</p>
    <div class="d-flex gap-3">
      <a href="#" class="text-decoration-none small">Privacy</a>
      <a href="#" class="text-decoration-none small">Terms</a>
      <a href="#" class="text-decoration-none small">Contact</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Smooth anchor scroll
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', e=>{
      const id = a.getAttribute('href');
      if(id.length>1){
        e.preventDefault();
        document.querySelector(id)?.scrollIntoView({behavior:'smooth', block:'start'});
        history.pushState(null,null,id);
      }
    });
  });
</script>
</body>
</html>
