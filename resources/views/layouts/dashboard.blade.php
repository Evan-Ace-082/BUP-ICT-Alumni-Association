<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard — BUP ICT Alumni Association')</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
  <div class="dashboard-shell">
    <aside class="dashboard-sidebar">
      <div class="brand">BUP ICT Alumni</div>
      <nav class="mt-2">
        @include('partials.sidebar')
      </nav>
    </aside>
    <div class="dashboard-main">
      <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">@yield('page_title', 'Dashboard')</h1>
        <div class="d-flex align-items-center gap-3">
          @auth
            @php($unreadCount = auth()->user()->unreadNotifications()->count())
            @if ($unreadCount > 0)
              <a href="{{ route('dashboard.announcements.index') }}" class="small text-muted-slate">🔔 {{ $unreadCount }} unread</a>
            @endif
          @endauth
          <a href="{{ route('home') }}" class="small text-muted-slate">&larr; Back to site</a>
        </div>
      </header>
      @yield('content')
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
