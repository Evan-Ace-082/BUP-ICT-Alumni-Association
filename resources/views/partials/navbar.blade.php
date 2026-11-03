<nav class="navbar navbar-expand-lg site-navbar sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="{{ route('home') }}">
      BUP ICT Alumni
      <small>Connecting Generations, Building the Future.</small>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('batches.index') ? 'active' : '' }}" href="{{ route('batches.index') }}">Batches</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('alumni.directory') ? 'active' : '' }}" href="{{ route('alumni.directory') }}">Alumni Directory</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('alumni.network') ? 'active' : '' }}" href="{{ route('alumni.network') }}">Alumni Network</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}">Announcements</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('events.index') ? 'active' : '' }}" href="{{ route('events.index') }}">Events</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('gallery.index') ? 'active' : '' }}" href="{{ route('gallery.index') }}">Gallery</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('achievements.index') ? 'active' : '' }}" href="{{ route('achievements.index') }}">Achievements</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('committees.*') ? 'active' : '' }}" href="{{ route('committees.index') }}">Executive Committee</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('elections.results.*') ? 'active' : '' }}" href="{{ route('elections.results.index') }}">Election Results</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('jobs.index') ? 'active' : '' }}" href="{{ route('jobs.index') }}">Jobs</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        @auth
          @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
            <a href="{{ route('admin.index') }}" class="btn btn-brass btn-sm {{ request()->routeIs('admin.*') ? 'active' : '' }}">Admin Panel</a>
            <span class="border-start mx-1" style="height: 1.5rem;"></span>
          @endif
          <a href="{{ route('dashboard') }}" class="btn btn-outline-ink btn-sm">Dashboard</a>
          <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-ink btn-sm">Log out</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="btn btn-outline-ink btn-sm">Log in</a>
          <a href="{{ route('register') }}" class="btn btn-brass btn-sm">Join the Association</a>
        @endauth
      </div>
    </div>
  </div>
</nav>
