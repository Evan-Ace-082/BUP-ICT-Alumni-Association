<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'BUP ICT Alumni Association')</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-body">
  <div class="auth-split-wrapper">
    {{-- Left Brand Panel (Desktop) --}}
    <div class="auth-brand-side d-none d-lg-flex">
      <div class="auth-brand-mesh"></div>
      <div class="auth-brand-inner">
        <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-3 text-decoration-none text-white mb-5">
          <img src="{{ asset('images/bup-ict-logo.jpg') }}" alt="BUP ICT Logo" class="brand-logo" style="width: 54px; height: 54px;">
          <div>
            <div class="brand-title text-white fs-5">BUP ICT Alumni</div>
            <small class="text-white-50">Department of ICT &middot; BUP</small>
          </div>
        </a>

        <div class="my-auto py-4">
          <span class="badge bg-brass-subtle text-brass border border-brass-subtle px-3 py-1 font-mono mb-3">Official Association Portal</span>
          <h2 class="display-6 text-white fw-bold mb-3">Connecting Generations, Building the Future.</h2>
          <p class="text-muted-glass mb-4" style="max-width: 28rem;">
            Welcome to the verified member network for graduates of the Department of Information and Communication Technology at Bangladesh University of Professionals.
          </p>
          <div class="d-flex gap-3 align-items-center">
            <span class="badge bg-white-10 text-white font-mono">Batch 01 &ndash; 08+</span>
            <span class="text-white-50">&bull;</span>
            <span class="text-white-50 small">Official Lineage</span>
          </div>
        </div>

        <div class="mt-auto pt-4 border-top border-white-10 text-white-50 small">
          &copy; {{ date('Y') }} Department of ICT &middot; Mirpur Cantonment, Dhaka
        </div>
      </div>
    </div>

    {{-- Right Form Side --}}
    <div class="auth-form-side">
      <div class="auth-card-modern">
        <div class="d-block d-lg-none text-center mb-4">
          <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
            <img src="{{ asset('images/bup-ict-logo.jpg') }}" alt="BUP ICT Logo" class="brand-logo" style="width: 44px; height: 44px;">
            <span class="font-display fw-bold text-ink fs-5">BUP ICT Alumni</span>
          </a>
        </div>

        @yield('content')

        <p class="text-center small text-muted-slate mt-4 mb-0">
          <a href="{{ route('home') }}" class="text-decoration-none">&larr; Back to the homepage</a>
        </p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
