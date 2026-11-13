<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard — BUP ICT Alumni Association')</title>
  <script>
    (function () {
      const savedTheme = localStorage.getItem('bup_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', savedTheme);
      document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @stack('styles')
</head>
<body>
  <div class="dashboard-shell">
    <div class="dashboard-sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="dashboard-sidebar" id="dashboardSidebar">
      <div class="brand">
        <a href="{{ route('home') }}" class="text-white text-decoration-none d-flex align-items-center gap-2">
          <span>BUP ICT Alumni</span>
        </a>
        <button class="btn btn-sm text-white-50 d-lg-none p-0 border-0 fs-5" id="sidebarCloseBtn" type="button" aria-label="Close sidebar">&times;</button>
      </div>
      <nav class="mt-2">
        @include('partials.sidebar')
      </nav>
    </aside>
    <div class="dashboard-main">
      <header class="dashboard-header d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-ink d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle navigation">
            <span style="font-size: 1.1rem; line-height: 1;">&#9776;</span>
          </button>
          <h1 class="h4 mb-0 font-display">@yield('page_title', 'Dashboard')</h1>
        </div>
        <div class="d-flex align-items-center gap-3">
          @auth
            @php($unreadCount = auth()->user()->unreadNotifications()->count())
            @if ($unreadCount > 0)
              <a href="{{ route('dashboard.announcements.index') }}" class="small text-muted-slate text-decoration-none">🔔 {{ $unreadCount }} unread</a>
            @endif
          @endauth
          <a href="{{ route('home') }}" class="small text-muted-slate text-decoration-none">&larr; Back to site</a>
        </div>
      </header>
      @yield('content')
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('dashboardSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      const toggleBtn = document.getElementById('sidebarToggleBtn');
      const closeBtn = document.getElementById('sidebarCloseBtn');

      function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
      }

      function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
      }

      if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
      if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
      if (backdrop) backdrop.addEventListener('click', closeSidebar);
    });
  </script>
  @stack('scripts')
</body>
</html>
