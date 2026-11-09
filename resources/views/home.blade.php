@extends('layouts.app')

@section('title', 'BUP ICT Alumni Association — Connecting Generations, Building the Future.')

@section('content')

  {{-- Hero Section --}}
  <section class="hero">
    <div class="container position-relative" style="z-index: 2;">
      <div class="row align-items-center gy-4">
        <div class="col-lg-8">
          <div class="live-pill mb-3">
            <span class="pulse-indicator"></span>
            <span>Department of ICT &middot; Bangladesh University of Professionals</span>
          </div>
          <h1 class="hero-title mb-3">
            Connecting Generations,<br>
            <span class="text-gradient-brass">Building the Future.</span>
          </h1>
          <p class="hero-lead mb-4">
            The official community and network for graduates of the Department of ICT — an unbroken chain of
            batches, {{ $batchCount }} and growing, advancing technology and leadership across the globe.
          </p>
          <div class="d-flex gap-3 flex-wrap">
            @auth
              <a href="{{ route('dashboard') }}" class="btn btn-brass btn-lg px-4 d-inline-flex align-items-center gap-2">
                <span>Go to Dashboard</span>
                <span>&rarr;</span>
              </a>
            @else
              <a href="{{ route('register') }}" class="btn btn-brass btn-lg px-4 d-inline-flex align-items-center gap-2">
                <span>Join the Association</span>
                <span>&rarr;</span>
              </a>
            @endauth
            <a href="{{ route('batches.index') }}" class="btn btn-outline-glass btn-lg px-4">Browse All Batches</a>
          </div>
        </div>

        {{-- Quick Stats Card in Hero --}}
        <div class="col-lg-4 d-none d-lg-block">
          <div class="hero-metric-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="metric-label">Network Status</span>
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
            </div>
            <div class="metric-value mb-1">{{ $batchCount }}</div>
            <div class="metric-subtext mb-4">Official Batches Covered (B01 &ndash; B{{ str_pad($batchCount, 2, '0', STR_PAD_LEFT) }})</div>
            <hr class="border-white-10 my-3">
            <div class="d-flex justify-content-between text-muted-glass small">
              <span>Department</span>
              <span class="text-white">ICT &middot; BUP</span>
            </div>
          </div>
        </div>
      </div>

      {{-- Signature element: the Generation Ribbon --}}
      <div class="generation-ribbon-wrapper mt-5">
        <div class="ribbon-header d-flex justify-content-between align-items-center mb-2">
          <span class="ribbon-label">Batch Continuity Chain</span>
          <span class="small text-muted-glass">Scroll to browse &rarr;</span>
        </div>
        <div class="generation-ribbon" role="list" aria-label="Alumni batches">
          @forelse ($batches as $batch)
            <a href="{{ route('batches.show', $batch) }}" class="ribbon-marker text-decoration-none" role="listitem">
              <span class="code">{{ $batch->batch_code }}</span>
              <span class="name">{{ $batch->ict_name }}</span>
            </a>
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
    </div>
  </section>

  {{-- Batch Generations Showcase Carousel --}}
  <section class="batch-carousel-section">
    <div class="container">
      <div class="section-header-row mb-4">
        <div>
          <span class="section-eyebrow">Our Generations</span>
          <h2 class="section-title text-white mb-0">Every batch, one frame at a time.</h2>
        </div>
        <div class="carousel-nav-buttons d-flex gap-2">
          <button class="carousel-nav-btn" type="button" data-bs-target="#batchCarousel" data-bs-slide="prev" aria-label="Previous Batch">
            &#8592;
          </button>
          <button class="carousel-nav-btn" type="button" data-bs-target="#batchCarousel" data-bs-slide="next" aria-label="Next Batch">
            &#8594;
          </button>
        </div>
      </div>

      <div class="batch-showcase-frame">
        <div id="batchCarousel" class="carousel carousel-fade slide batch-carousel" data-bs-ride="carousel" data-bs-interval="2000" data-bs-touch="true">

          {{-- Modern Batch Pill Indicators --}}
          <div class="batch-pill-indicators">
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="0" class="active batch-tab-pill" aria-label="B01">
              <span class="pill-dot"></span> B01
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="1" class="batch-tab-pill placeholder-pill" aria-label="B02">
              <span class="pill-dot"></span> B02
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="2" class="batch-tab-pill" aria-label="B03">
              <span class="pill-dot"></span> B03
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="3" class="batch-tab-pill" aria-label="B04">
              <span class="pill-dot"></span> B04
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="4" class="batch-tab-pill" aria-label="B05">
              <span class="pill-dot"></span> B05
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="5" class="batch-tab-pill" aria-label="B06">
              <span class="pill-dot"></span> B06
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="6" class="batch-tab-pill" aria-label="B07">
              <span class="pill-dot"></span> B07
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="7" class="batch-tab-pill" aria-label="B08">
              <span class="pill-dot"></span> B08
            </button>
          </div>

          <div class="carousel-inner">

            {{-- B01 Live --}}
            <div class="carousel-item active">
              <img src="{{ asset('images/batches/b01.jpg') }}" class="batch-slide-img" alt="ICT Batch 01 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">অরিত্র ১৬</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 01</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 01</h3>
                  <small class="text-muted-glass">Department Pioneer Generation &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B02 Placeholder --}}
            <div class="carousel-item">
              <div class="batch-placeholder-slide">
                <div class="batch-placeholder-inner">
                  <div class="batch-placeholder-badge">BATCH 02</div>
                  <span class="batch-placeholder-code">B02</span>
                  <span class="batch-placeholder-label">Official Batch Photo Coming Soon</span>
                </div>
              </div>
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="batch-caption-pill">Pioneer Generation</div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 02</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B03 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b03.jpg') }}" class="batch-slide-img" alt="ICT Batch 03 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">স্যমন্তক ১৭</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 03</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 03</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B04 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b04.jpg') }}" class="batch-slide-img" alt="ICT Batch 04 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">সংবরণ ১৮</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 04</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 04</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B05 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b05.jpg') }}" class="batch-slide-img" alt="ICT Batch 05 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">সপ্তক ১৯</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 05</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 05</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B06 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b06.jpg') }}" class="batch-slide-img" alt="ICT Batch 06 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">সম্বরণ ২০</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 06</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 06</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B07 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b07.jpg') }}" class="batch-slide-img" alt="ICT Batch 07 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">সপ্তর্ষি ২১</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 07</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 07</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

            {{-- B08 Live --}}
            <div class="carousel-item">
              <img src="{{ asset('images/batches/b08.jpg') }}" class="batch-slide-img" alt="ICT Batch 08 graduation photo">
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">অন্তরীক্ষ ২২</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 08</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 08</h3>
                  <small class="text-muted-glass">Department of ICT &middot; BUP</small>
                </div>
              </div>
            </div>

          </div>{{-- /.carousel-inner --}}
        </div>{{-- /#batchCarousel --}}
      </div>
    </div>
  </section>

  {{-- Association Architecture Pillars --}}
  <section class="section">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-5">
          <div class="section-eyebrow mb-2">About the Association</div>
          <h2 class="mb-3">A permanent digital home for every batch that follows.</h2>
          <p class="text-muted-slate mb-4">
            The BUP ICT Alumni Association connects every graduating cohort of the
            Department of ICT — facilitating mentorship, career progression, departmental
            collaborations, and lifelong professional bonds.
          </p>
          <div class="d-flex gap-3">
            <a href="{{ route('about') }}" class="btn btn-outline-ink btn-sm px-3">Read Our Mission &rarr;</a>
            <a href="{{ route('alumni.directory') }}" class="btn btn-ghost-sm btn-sm">Search Alumni Directory</a>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card-feature h-100">
                <div class="feature-icon-badge">
                  <span>01</span>
                </div>
                <h3 class="h6 fw-semibold mb-2">Batch-First Architecture</h3>
                <p class="small text-muted-slate mb-0">Every alumni profile is structurally anchored to an ICT cohort, preserving historical lineage and batch identities seamlessly.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card-feature h-100">
                <div class="feature-icon-badge">
                  <span>02</span>
                </div>
                <h3 class="h6 fw-semibold mb-2">Verified Digital Identity</h3>
                <p class="small text-muted-slate mb-0">Official academic credentials and claims are verified with tamper-proof QR code digital ID cards and department oversight.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card-feature h-100">
                <div class="feature-icon-badge">
                  <span>03</span>
                </div>
                <h3 class="h6 fw-semibold mb-2">Career & Mentorship Exchange</h3>
                <p class="small text-muted-slate mb-0">Exclusive alumni-to-student and peer mentorship channels, vetted tech job openings, and career development initiatives.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card-feature h-100">
                <div class="feature-icon-badge">
                  <span>04</span>
                </div>
                <h3 class="h6 fw-semibold mb-2">Democratic Governance</h3>
                <p class="small text-muted-slate mb-0">Transparent election cycles, audited ballot voting, and structured executive committee tenures documented permanently.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Metrics & Impact --}}
  <section class="section bg-parchment border-top border-bottom">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
          <div class="section-eyebrow mb-2">At a Glance</div>
          <h2 class="mb-0">A foundation built to scale</h2>
        </div>
        <span class="badge bg-parchment text-brass border px-3 py-2 font-mono small">Live Platform Metrics</span>
      </div>

      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-top-accent"></div>
            <div class="stat-number">{{ $batchCount }}</div>
            <div class="stat-label">Batches Covered</div>
            <div class="stat-desc">B01 through present</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-top-accent"></div>
            <div class="stat-number">100%</div>
            <div class="stat-label">Verified Lineage</div>
            <div class="stat-desc">Department authenticated</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-top-accent"></div>
            <div class="stat-number">4</div>
            <div class="stat-label">Governance Roles</div>
            <div class="stat-desc">Tiered access architecture</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-top-accent"></div>
            <div class="stat-number">&infin;</div>
            <div class="stat-label">Lifelong Bonds</div>
            <div class="stat-desc">Bridging all generations</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Explore the Association Services --}}
  <section class="section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
          <div class="section-eyebrow mb-2">Explore the Association</div>
          <h2 class="mb-0">Everything alumni can access here</h2>
        </div>
        <a href="{{ route('batches.index') }}" class="btn btn-outline-ink btn-sm">Browse Batches</a>
      </div>

      <div class="row g-3">
        <div class="col-6 col-md-4">
          <a href="{{ route('events.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">01</div>
              <h3 class="h6 fw-semibold mb-1">Events & Reunions</h3>
              <p class="small text-muted-slate mb-3">Reunions, technical seminars, and networking meetups across batches.</p>
              <span class="card-action-link">View upcoming &rarr;</span>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('gallery.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">02</div>
              <h3 class="h6 fw-semibold mb-1">Campus Gallery</h3>
              <p class="small text-muted-slate mb-3">Photo archives and memories from convocations and campus life.</p>
              <span class="card-action-link">Explore albums &rarr;</span>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('achievements.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">03</div>
              <h3 class="h6 fw-semibold mb-1">Hall of Achievements</h3>
              <p class="small text-muted-slate mb-3">Honoring career accomplishments, research, and community contributions.</p>
              <span class="card-action-link">See honorees &rarr;</span>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('jobs.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">04</div>
              <h3 class="h6 fw-semibold mb-1">Alumni Job Board</h3>
              <p class="small text-muted-slate mb-3">Exclusive career listings posted by alumni for fellow department graduates.</p>
              <span class="card-action-link">Browse jobs &rarr;</span>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('alumni.directory') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">05</div>
              <h3 class="h6 fw-semibold mb-1">Alumni Directory</h3>
              <p class="small text-muted-slate mb-3">Search, locate, and message graduates from any cohort or company.</p>
              <span class="card-action-link">Open directory &rarr;</span>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4">
          <a href="{{ route('batches.index') }}" class="text-decoration-none text-reset">
            <div class="card-feature card-interactive h-100">
              <div class="feature-icon-badge">06</div>
              <h3 class="h6 fw-semibold mb-1">Batch Roster</h3>
              <p class="small text-muted-slate mb-3">Comprehensive records and batch representatives for all generations.</p>
              <span class="card-action-link">Browse roster &rarr;</span>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  {{-- Call to Action Card --}}
  <section class="section py-5 bg-ink text-white">
    <div class="container">
      <div class="cta-banner-card text-center">
        <div class="live-pill mb-3 mx-auto">
          <span class="pulse-indicator"></span>
          <span>Official Membership Portal</span>
        </div>
        <h2 class="text-white mb-3">Ready to claim your alumni profile?</h2>
        <p class="text-muted-glass mb-4 mx-auto" style="max-width: 38rem;">
          Verify your student ID, connect with your batchmates, receive event invitations, and gain access to the verified members dashboard.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          @auth
            <a href="{{ route('dashboard') }}" class="btn btn-brass btn-lg px-5">Go to Dashboard</a>
          @else
            <a href="{{ route('register') }}" class="btn btn-brass btn-lg px-5">Join the Association</a>
            <a href="{{ route('login') }}" class="btn btn-outline-glass btn-lg px-4">Member Login</a>
          @endauth
        </div>
      </div>
    </div>
  </section>

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const carouselEl = document.getElementById('batchCarousel');
    if (!carouselEl) return;

    const pills = carouselEl.querySelectorAll('.batch-pill-indicators .batch-tab-pill');

    carouselEl.addEventListener('slide.bs.carousel', function (event) {
      const nextIndex = event.to;
      pills.forEach(function (pill, idx) {
        if (idx === nextIndex) {
          pill.classList.add('active');
          pill.setAttribute('aria-current', 'true');
        } else {
          pill.classList.remove('active');
          pill.removeAttribute('aria-current');
        }
      });
    });
  });
</script>
@endpush
