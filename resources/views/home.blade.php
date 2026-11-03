@extends('layouts.app')

@section('title', 'BUP ICT Alumni Association — Connecting Generations, Building the Future.')

@section('content')

  {{-- Hero --}}
  <section class="hero">
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <div class="eyebrow mb-3">Department of ICT &middot; Bangladesh University of Professionals</div>
          <h1 class="mb-3">Connecting Generations,<br>Building the Future.</h1>
          <p class="lead mb-4">
            The official home for graduates of the Department of ICT — a growing chain of
            batches, {{ $batchCount }} and counting, staying connected long after convocation.
          </p>
          <div class="d-flex gap-3 flex-wrap">
            @auth
              <a href="{{ route('dashboard') }}" class="btn btn-brass btn-lg px-4">Go to Dashboard</a>
            @else
              <a href="{{ route('register') }}" class="btn btn-brass btn-lg px-4">Join the Association</a>
            @endauth
            <a href="{{ route('batches.index') }}" class="btn btn-outline-light btn-lg px-4">Browse Batches</a>
          </div>
        </div>
      </div>

      {{-- Signature element: the Generation Ribbon — a literal, ordered
           chain of batches, because that ordering is real information. --}}
      <div class="generation-ribbon mt-5" role="list" aria-label="Alumni batches">
        @forelse ($batches as $batch)
          <div class="ribbon-marker" role="listitem">
            <span class="code">{{ $batch->batch_code }}</span>
            <span class="name">{{ $batch->ict_name }}</span>
          </div>
        @empty
          <div class="ribbon-marker" role="listitem">
            <span class="code">B01</span>
            <span class="name">Coming soon</span>
          </div>
        @endforelse
        <div class="ribbon-marker is-next" role="listitem" aria-label="Future batches">
          <span class="code">NEXT</span>
          <span class="name">&hellip;</span>
        </div>
      </div>
    </div>
  </section>

  {{-- Association introduction --}}
  <section class="section">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-6">
          <div class="section-eyebrow mb-2">About the Association</div>
          <h2 class="mb-3">A permanent home for every batch that follows.</h2>
          <p class="text-muted-slate">
            The BUP ICT Alumni Association exists to keep every graduating batch of the
            Department of ICT connected — to each other, to current students, and to the
            department itself. The alumni directory, mentorship, events, gallery, achievement
            wall, and job board are all live today, with elections planned for a future phase.
          </p>
        </div>
        <div class="col-lg-6">
          <div class="row g-3">
            <div class="col-6">
              <div class="card-feature">
                <div class="icon-badge">01</div>
                <h3 class="h6">Batch-first architecture</h3>
                <p class="small text-muted-slate mb-0">Every alumni record belongs to a batch — and new batches are added without touching the codebase.</p>
              </div>
            </div>
            <div class="col-6">
              <div class="card-feature">
                <div class="icon-badge">02</div>
                <h3 class="h6">Verified records</h3>
                <p class="small text-muted-slate mb-0">Official alumni data, kept separate from login accounts, so a record can exist before anyone claims it.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Statistics (safe development placeholders — not real figures) --}}
  <section class="section bg-parchment">
    <div class="container">
      <div class="section-eyebrow mb-2">At a Glance</div>
      <h2 class="mb-4">A foundation built to grow</h2>
      <div class="row g-4">
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $batchCount }}</div>
          <div class="stat-label">Batches Covered</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">&mdash;</div>
          <div class="stat-label">Verified Alumni</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">&mdash;</div>
          <div class="stat-label">Upcoming Events</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">4</div>
          <div class="stat-label">Association Roles</div>
        </div>
      </div>
      <p class="small text-muted-slate mt-3 mb-0">
        Figures marked &ldquo;&mdash;&rdquo; will populate once real alumni data is imported in a later phase.
      </p>
    </div>
  </section>

  {{-- Alumni batches --}}
  <section class="section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
          <div class="section-eyebrow mb-2">Batches</div>
          <h2 class="mb-0">Currently covered generations</h2>
        </div>
        <a href="{{ route('batches.index') }}" class="btn btn-outline-ink">View all batches</a>
      </div>
      <div class="row g-3">
        @forelse ($batches->take(8) as $batch)
          <div class="col-6 col-md-3">
            <a href="{{ route('batches.show', $batch) }}" class="text-decoration-none text-reset d-block h-100">
              <div class="card-feature text-center h-100">
                <div class="icon-badge mx-auto">{{ $batch->ict_name }}</div>
                <h3 class="h6 mb-0">{{ $batch->ict_name }}</h3>
              </div>
            </a>
          </div>
        @empty
          <div class="col-12">
            <div class="placeholder-panel">Batches will appear here once seeded.</div>
          </div>
        @endforelse
      </div>
    </div>
  </section>

  {{-- Explore the Association --}}
  <section class="section bg-parchment">
    <div class="container">
      <div class="section-eyebrow mb-2">Explore the Association</div>
      <h2 class="mb-4">Everything alumni can do here</h2>
      <div class="row g-3">
        <div class="col-6 col-md-4">
          <a href="{{ route('events.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">01</div>
              <h3 class="h6 mb-1">Events</h3>
              <p class="small text-muted-slate mb-0">Reunions, seminars, and meetups from across every batch.</p>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('gallery.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">02</div>
              <h3 class="h6 mb-1">Gallery</h3>
              <p class="small text-muted-slate mb-0">Photo albums from reunions, workshops, and department life.</p>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('achievements.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">03</div>
              <h3 class="h6 mb-1">Achievements</h3>
              <p class="small text-muted-slate mb-0">Celebrating career, academic, and community milestones.</p>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('jobs.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">04</div>
              <h3 class="h6 mb-1">Job Board</h3>
              <p class="small text-muted-slate mb-0">Opportunities posted by alumni, for alumni.</p>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('alumni.directory') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">05</div>
              <h3 class="h6 mb-1">Alumni Directory</h3>
              <p class="small text-muted-slate mb-0">Search and connect with graduates from any batch.</p>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('batches.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature h-100">
              <div class="icon-badge">06</div>
              <h3 class="h6 mb-1">Batches</h3>
              <p class="small text-muted-slate mb-0">Browse every generation the association currently covers.</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  {{-- Events CTA --}}
  <section class="section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
          <div class="section-eyebrow mb-2">Upcoming Events</div>
          <h2 class="mb-0">Reunions, seminars, and meetups</h2>
        </div>
        <a href="{{ route('events.index') }}" class="btn btn-outline-ink">View all events</a>
      </div>
      <p class="text-muted-slate mb-0">
        See what's coming up across every batch, or filter for events targeted at your own.
      </p>
    </div>
  </section>

  {{-- Call to action --}}
  <section class="section bg-ink text-center text-white">
    <div class="container">
      <h2 class="text-white mb-3">Already an ICT graduate?</h2>
      @auth
        <p class="mb-4" style="color: rgba(255,255,255,.75);">Pick up right where you left off.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-brass btn-lg px-5">Go to Dashboard</a>
      @else
        <p class="mb-4" style="color: rgba(255,255,255,.75);">Claim your alumni record and stay connected with your batch.</p>
        <a href="{{ route('register') }}" class="btn btn-brass btn-lg px-5">Join the Association</a>
      @endauth
    </div>
  </section>

@endsection
