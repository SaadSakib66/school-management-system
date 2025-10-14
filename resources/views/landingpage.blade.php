<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Education Management System</title>

  <!-- Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom -->
  <link rel="stylesheet" href="{{ asset('front/css/styles.css') }}" />
</head>
<body class="bg-white">

  <!-- Top Nav -->
  <nav class="navbar navbar-expand-lg bg-white sticky-top py-3 border-bottom-0">
    <div class="container-xxl align-items-center">
      <a class="navbar-brand" href="{{ route('landing') }}">
        <img src="{{ asset('front/images/logo.png') }}" alt="Logo" height="48">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
              aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="nav" class="collapse navbar-collapse justify-content-end">
        <ul class="navbar-nav fw-bold gap-lg-4">
          <li class="nav-item"><a class="nav-link text-nav" href="#features">Features</a></li>
          <li class="nav-item"><a class="nav-link text-nav" href="#modules">Module</a></li>
          <li class="nav-item"><a class="nav-link text-nav" href="#faq">FAQ</a></li>
          <li class="nav-item"><a class="nav-link text-nav" href="{{ route('admin.login.page') }}">Login</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero position-relative overflow-hidden">
    <div class="container-xxl">
      <div class="row align-items-center g-4">
        <!-- Left -->
        <div class="col-lg-6">
          <h1 class="display-5 fw-bold lh-sm text-ems mb-3">
            The Ultimate Education<br>
            Management System<br>
            <span class="text-muted2 fw-bold">for</span>
            <span class="highlight">School, Institute &amp; Academy</span>
          </h1>

          <p class="lead text-secondary hero-subcopy mb-4">
            All-in-one platform for classes, subjects, teachers, parents, exams, timetables,
            attendance, homework, marks, fees and more.
          </p>

          <a href="{{ route('admin.login.page') }}" class="btn btn-gradient px-4 py-2 fw-bold">LOG IN</a>
        </div>

        <!-- Right artwork -->
        <div class="col-lg-6 art-col">
          <div class="hero-art w-100" aria-hidden="true"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- MODULES SECTION -->
  <section id="modules" class="modules-section position-relative overflow-hidden py-5">
    <div class="container-xxl position-relative" style="z-index:1;">

      <!-- Anchor BEFORE the dashboard so clicking 'Features' shows the dashboard -->
      <div id="features"></div>

      <h2 class="h3 fw-bold text-center text-ems mb-5 lh-tight">
        <span class="underline-anim u-close">
          Every Single Module You <br class="d-none d-md-inline">
          Want That Are Available
        </span>
      </h2>

      <!-- Dashboard Image -->
      <div class="text-center mb-5">
        <img src="{{ asset('front/images/dashboard.png') }}" alt="Dashboard"
             class="dash-shot img-fluid shadow-lg rounded-4">
      </div>

      <!-- 8 Responsive Cards -->
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Admin Module</h5>
            <p>Manage accounts, schools, teachers, students, guardians, etc.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Student Info</h5>
            <p>Admission, student list, attendance, promote, reports, more.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Teacher</h5>
            <p>Materials, assignments, syllabus download &amp; more.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Accounts</h5>
            <p>Profit, income, expense, account list, payment methods.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Examination</h5>
            <p>Exam routine, schedule, seat plan, mark sheet &amp; reports.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Library</h5>
            <p>Books, member cards, issue/return, categories &amp; lists.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">Reports</h5>
            <p>Class, attendance, progress card &amp; custom reports.</p>
          </div>
        </div>

        <div class="col">
          <div class="module-card h-100">
            <h5 class="fw-bold text-ems">System Settings</h5>
            <p>General settings, email, permission, backup &amp; update.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- BRAND BANNER -->
  <section class="brand-banner position-relative overflow-hidden" id="faq">
    <div class="container-xxl">
      <div class="row justify-content-center">
        <div class="col-12 text-center py-5 py-lg-6">
          <img src="{{ asset('front/images/logo.png') }}" alt="BAR & Associates" class="brand-banner__logo mb-3">
          <h2 class="brand-banner__title m-0">
            World Best Software
            <br class="d-none d-md-inline">
            One Stop Solution
          </h2>
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
