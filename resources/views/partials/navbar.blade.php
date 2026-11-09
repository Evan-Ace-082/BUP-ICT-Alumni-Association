<nav class="navbar navbar-expand-lg site-navbar sticky-top py-2">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('home') }}">
      <img src="{{ asset('images/bup-ict-logo.jpg') }}" alt="BUP ICT Logo" class="brand-logo">
      <div class="brand-text-block">
        <div class="brand-title">BUP ICT Alumni</div>
        <small class="brand-tagline">Connecting Generations &middot; Building the Future</small>
      </div>
    </a>
    <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <ul class="navbar-nav nav-dock mx-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('batches.*') ? 'active' : '' }}" href="{{ route('batches.index') }}">Batches</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('alumni.directory') ? 'active' : '' }}" href="{{ route('alumni.directory') }}">Alumni Directory</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('alumni.network') ? 'active' : '' }}" href="{{ route('alumni.network') }}">Alumni Network</a>
        </li>
        
        {{-- Community Dropdown --}}
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('events.*', 'announcements.*', 'gallery.*', 'achievements.*', 'jobs.*') ? 'active' : '' }}" href="#" id="communityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Community
          </a>
          <ul class="dropdown-menu dropdown-menu-modern shadow-soft border-0" aria-labelledby="communityDropdown">
            <li><a class="dropdown-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}"><span class="dropdown-bullet"></span>Announcements</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('events.*') ? 'active' : '' }}" href="{{ route('events.index') }}"><span class="dropdown-bullet"></span>Events</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('gallery.*') ? 'active' : '' }}" href="{{ route('gallery.index') }}"><span class="dropdown-bullet"></span>Gallery</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('achievements.*') ? 'active' : '' }}" href="{{ route('achievements.index') }}"><span class="dropdown-bullet"></span>Achievements</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item {{ request()->routeIs('jobs.*') ? 'active' : '' }}" href="{{ route('jobs.index') }}"><span class="dropdown-bullet"></span>Jobs</a></li>
          </ul>
        </li>

        {{-- Governance Dropdown --}}
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('committees.*', 'elections.results.*') ? 'active' : '' }}" href="#" id="govDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Governance
          </a>
          <ul class="dropdown-menu dropdown-menu-modern shadow-soft border-0" aria-labelledby="govDropdown">
            <li><a class="dropdown-item {{ request()->routeIs('committees.*') ? 'active' : '' }}" href="{{ route('committees.index') }}"><span class="dropdown-bullet"></span>Executive Committee</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('elections.results.*') ? 'active' : '' }}" href="{{ route('elections.results.index') }}"><span class="dropdown-bullet"></span>Election Results</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
        {{-- Theme Mode Toggle Button --}}
        <button id="themeToggleBtn" type="button" class="btn btn-theme-toggle" aria-label="Toggle dark/light theme" title="Toggle theme">
          <span class="theme-icon-dark">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
          </span>
          <span class="theme-icon-light">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
          </span>
        </button>

        @auth
          @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
            <a href="{{ route('admin.index') }}" class="btn btn-outline-brass btn-sm {{ request()->routeIs('admin.*') ? 'active' : '' }}">Admin Panel</a>
          @endif
          <a href="{{ route('dashboard') }}" class="btn btn-ink btn-sm">Dashboard</a>
          <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-ghost-sm btn-sm">Log out</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="btn btn-nav-login btn-sm">Log in</a>
          <a href="{{ route('register') }}" class="btn btn-nav-join btn-brass btn-sm px-3 d-inline-flex align-items-center gap-1">
            <span>Join Association</span>
            <span class="btn-arrow">&rarr;</span>
          </a>
        @endauth
      </div>
    </div>
  </div>
</nav>
