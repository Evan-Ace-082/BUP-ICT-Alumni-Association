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

      <div class="batch-showcase-frame position-relative">
        {{-- Click to Zoom & Pan Indicator Badge --}}
        <button type="button" class="batch-zoom-trigger-badge" id="batchZoomTriggerBadge" title="Click to zoom and pan this batch photo" aria-label="Zoom in on current batch photograph">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="11" y1="8" x2="11" y2="14"></line>
            <line x1="8" y1="11" x2="14" y2="11"></line>
          </svg>
          <span>Zoom &amp; Pan Photo</span>
        </button>

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
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="8" class="batch-tab-pill placeholder-pill" aria-label="B09">
              <span class="pill-dot"></span> B09
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="9" class="batch-tab-pill placeholder-pill" aria-label="B10">
              <span class="pill-dot"></span> B10
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="10" class="batch-tab-pill placeholder-pill" aria-label="B11">
              <span class="pill-dot"></span> B11
            </button>
            <button type="button" data-bs-target="#batchCarousel" data-bs-slide-to="11" class="batch-tab-pill placeholder-pill" aria-label="B12">
              <span class="pill-dot"></span> B12
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">Pioneer Generation</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 02</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 02</h3>
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- B09 Placeholder --}}
            <div class="carousel-item">
              <div class="batch-placeholder-slide">
                <div class="batch-placeholder-inner">
                  <div class="batch-placeholder-badge">BATCH 09</div>
                  <span class="batch-placeholder-code">B09</span>
                  <span class="batch-placeholder-label">Official Batch Photo Coming Soon</span>
                </div>
              </div>
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">Cohort 23</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 09</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 09</h3>
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- B10 Placeholder --}}
            <div class="carousel-item">
              <div class="batch-placeholder-slide">
                <div class="batch-placeholder-inner">
                  <div class="batch-placeholder-badge">BATCH 10</div>
                  <span class="batch-placeholder-code">B10</span>
                  <span class="batch-placeholder-label">Official Batch Photo Coming Soon</span>
                </div>
              </div>
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">Cohort 24</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 10</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 10</h3>
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- B11 Placeholder --}}
            <div class="carousel-item">
              <div class="batch-placeholder-slide">
                <div class="batch-placeholder-inner">
                  <div class="batch-placeholder-badge">BATCH 11</div>
                  <span class="batch-placeholder-code">B11</span>
                  <span class="batch-placeholder-label">Official Batch Photo Coming Soon</span>
                </div>
              </div>
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">Cohort 25</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 11</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 11</h3>
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- B12 Placeholder --}}
            <div class="carousel-item">
              <div class="batch-placeholder-slide">
                <div class="batch-placeholder-inner">
                  <div class="batch-placeholder-badge">BATCH 12</div>
                  <span class="batch-placeholder-code">B12</span>
                  <span class="batch-placeholder-label">Official Batch Photo Coming Soon</span>
                </div>
              </div>
              <div class="carousel-caption batch-caption">
                <div class="batch-caption-card">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="batch-caption-pill">Cohort 26</span>
                    <span class="badge bg-brass-subtle text-brass">Batch 12</span>
                  </div>
                  <h3 class="batch-caption-name mb-0">ICT Batch 12</h3>
                  <div class="batch-caption-dept">
                    <div class="dept-name">Department of Information and Communication Technology</div>
                    <div class="inst-name">Bangladesh University of Professionals (BUP)</div>
                  </div>
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
  <section class="section section-highlight">
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

  {{-- Interactive Fullscreen Batch Photo Zoom & Pan Modal --}}
  <div id="batchPhotoZoomModal" class="batch-zoom-modal" aria-hidden="true" role="dialog" aria-label="Batch Photo Zoom Viewer">
    <div class="batch-zoom-backdrop" id="batchZoomBackdrop"></div>

    {{-- Top Header Bar --}}
    <div class="batch-zoom-header">
      <div class="batch-zoom-title-area">
        <span class="batch-zoom-badge" id="batchZoomBadge">Batch 01</span>
        <h3 class="batch-zoom-title mb-0" id="batchZoomTitle">ICT Batch 01</h3>
        <span class="batch-zoom-subtext d-none d-sm-inline" id="batchZoomSubtext">অরিত্র ১৬</span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="button" class="batch-zoom-close-btn" id="batchZoomCloseBtn" title="Close viewer (Esc)" aria-label="Close viewer">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    {{-- Center Viewport --}}
    <div class="batch-zoom-viewport" id="batchZoomViewport">
      <div class="batch-zoom-stage" id="batchZoomStage">
        <img src="" alt="" id="batchZoomImg" class="batch-zoom-img" draggable="false">
      </div>
    </div>

    {{-- Bottom Control Toolbar --}}
    <div class="batch-zoom-toolbar" role="toolbar" aria-label="Zoom and pan controls">
      <button type="button" class="batch-zoom-tool-btn" id="batchZoomOutBtn" title="Zoom Out (-)" aria-label="Zoom Out">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
      </button>

      <span class="batch-zoom-percent" id="batchZoomPercent" title="Current zoom level">100%</span>

      <button type="button" class="batch-zoom-tool-btn" id="batchZoomInBtn" title="Zoom In (+)" aria-label="Zoom In">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="11" y1="8" x2="11" y2="14"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
      </button>

      <div class="batch-zoom-divider"></div>

      <button type="button" class="batch-zoom-tool-btn" id="batchZoomResetBtn" title="Reset to original size (R or 0)" aria-label="Reset zoom">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
          <path d="M3 3v5h5"></path>
        </svg>
        <span class="d-none d-md-inline ms-1 small fw-semibold">Reset</span>
      </button>
    </div>

    {{-- Interaction Hint --}}
    <div class="batch-zoom-hint d-none d-sm-block">
      Scroll / Pinch to zoom &middot; Click &amp; drag to pan &middot; Double-click to toggle &middot; Esc to close
    </div>
  </div>

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const carouselEl = document.getElementById('batchCarousel');
    if (!carouselEl) return;

    const pills = carouselEl.querySelectorAll('.batch-pill-indicators .batch-tab-pill');
    const triggerBadge = document.getElementById('batchZoomTriggerBadge');

    function updateTriggerBadgeVisibility(activeItem) {
      if (!triggerBadge) return;
      const hasImage = activeItem && activeItem.querySelector('.batch-slide-img');
      triggerBadge.style.display = hasImage ? 'inline-flex' : 'none';
    }

    // Initial badge visibility check
    const initialActive = carouselEl.querySelector('.carousel-item.active');
    updateTriggerBadgeVisibility(initialActive);

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

      const items = carouselEl.querySelectorAll('.carousel-item');
      if (items[nextIndex]) {
        updateTriggerBadgeVisibility(items[nextIndex]);
      }
    });

    // Zoom & Pan Viewer Logic
    const modal = document.getElementById('batchPhotoZoomModal');
    if (!modal) return;

    const backdrop = document.getElementById('batchZoomBackdrop');
    const closeBtn = document.getElementById('batchZoomCloseBtn');
    const viewport = document.getElementById('batchZoomViewport');
    const stage = document.getElementById('batchZoomStage');
    const modalImg = document.getElementById('batchZoomImg');
    const modalBadge = document.getElementById('batchZoomBadge');
    const modalTitle = document.getElementById('batchZoomTitle');
    const modalSubtext = document.getElementById('batchZoomSubtext');
    const zoomInBtn = document.getElementById('batchZoomInBtn');
    const zoomOutBtn = document.getElementById('batchZoomOutBtn');
    const resetBtn = document.getElementById('batchZoomResetBtn');
    const zoomPercentLabel = document.getElementById('batchZoomPercent');

    let currentScale = 1;
    let currentPanX = 0;
    let currentPanY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let initialPinchDistance = null;
    let initialPinchScale = 1;
    let carouselInstance = null;

    const minScale = 1;
    const maxScale = 5;

    function clampPan() {
      if (currentScale <= 1.05) {
        currentPanX = 0;
        currentPanY = 0;
        return;
      }
      const vpWidth = viewport.clientWidth;
      const vpHeight = viewport.clientHeight;
      const imgWidth = (modalImg.offsetWidth || (vpWidth * 0.9)) * currentScale;
      const imgHeight = (modalImg.offsetHeight || (vpHeight * 0.75)) * currentScale;

      const maxPanX = Math.max(0, (imgWidth - vpWidth) / 2 + 100);
      const maxPanY = Math.max(0, (imgHeight - vpHeight) / 2 + 100);

      currentPanX = Math.min(Math.max(-maxPanX, currentPanX), maxPanX);
      currentPanY = Math.min(Math.max(-maxPanY, currentPanY), maxPanY);
    }

    function applyTransform(animated = false) {
      stage.style.transition = animated ? 'transform 0.22s cubic-bezier(0.16, 1, 0.3, 1)' : 'none';
      stage.style.transform = `translate3d(${currentPanX}px, ${currentPanY}px, 0) scale(${currentScale})`;
      zoomPercentLabel.textContent = `${Math.round(currentScale * 100)}%`;

      if (currentScale > 1.05) {
        viewport.style.cursor = isDragging ? 'grabbing' : 'grab';
      } else {
        viewport.style.cursor = 'default';
      }
    }

    function zoomBy(step, focalPoint = null) {
      const newScale = Math.min(Math.max(minScale, currentScale + step), maxScale);
      if (newScale === currentScale) return;

      if (focalPoint && currentScale > 0) {
        const scaleRatio = newScale / currentScale;
        currentPanX = focalPoint.x - (focalPoint.x - currentPanX) * scaleRatio;
        currentPanY = focalPoint.y - (focalPoint.y - currentPanY) * scaleRatio;
      } else if (newScale <= 1.05) {
        currentPanX = 0;
        currentPanY = 0;
      }

      currentScale = newScale;
      clampPan();
      applyTransform(true);
    }

    function openZoomViewer(imgEl) {
      if (!imgEl || !imgEl.src) return;

      const carouselItem = imgEl.closest('.carousel-item');
      const badgeText = carouselItem?.querySelector('.badge')?.textContent?.trim() || 'Batch Photo';
      const titleText = carouselItem?.querySelector('.batch-caption-name')?.textContent?.trim() || 'ICT Batch Photo';
      const subtext = carouselItem?.querySelector('.batch-caption-pill')?.textContent?.trim() || '';

      modalBadge.textContent = badgeText;
      modalTitle.textContent = titleText;
      modalSubtext.textContent = subtext;
      modalImg.src = imgEl.src;
      modalImg.alt = imgEl.alt || titleText;

      currentScale = 1;
      currentPanX = 0;
      currentPanY = 0;
      applyTransform(false);

      if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
        carouselInstance = bootstrap.Carousel.getInstance(carouselEl);
        if (carouselInstance) {
          carouselInstance.pause();
        }
      }

      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeZoomViewer() {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';

      if (carouselInstance) {
        carouselInstance.cycle();
      }

      currentScale = 1;
      currentPanX = 0;
      currentPanY = 0;
      applyTransform(false);
    }

    // Attach click events to all batch slide images
    carouselEl.querySelectorAll('.batch-slide-img').forEach(function (img) {
      img.addEventListener('click', function (e) {
        e.preventDefault();
        openZoomViewer(this);
      });
    });

    // Attach click to the trigger badge
    if (triggerBadge) {
      triggerBadge.addEventListener('click', function (e) {
        e.preventDefault();
        const activeImg = carouselEl.querySelector('.carousel-item.active .batch-slide-img');
        if (activeImg) {
          openZoomViewer(activeImg);
        }
      });
    }

    // Toolbar buttons
    zoomInBtn.addEventListener('click', () => zoomBy(0.5));
    zoomOutBtn.addEventListener('click', () => zoomBy(-0.5));
    resetBtn.addEventListener('click', () => {
      currentScale = 1;
      currentPanX = 0;
      currentPanY = 0;
      applyTransform(true);
    });

    closeBtn.addEventListener('click', closeZoomViewer);
    backdrop.addEventListener('click', closeZoomViewer);

    // Mouse wheel zoom towards cursor
    viewport.addEventListener('wheel', function (e) {
      e.preventDefault();
      const rect = viewport.getBoundingClientRect();
      const focalX = e.clientX - rect.left - rect.width / 2;
      const focalY = e.clientY - rect.top - rect.height / 2;
      const delta = e.deltaY < 0 ? 0.35 : -0.35;
      zoomBy(delta, { x: focalX, y: focalY });
    }, { passive: false });

    // Desktop Click & Drag to Pan
    viewport.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      if (currentScale <= 1.05) return;

      isDragging = true;
      dragStartX = e.clientX - currentPanX;
      dragStartY = e.clientY - currentPanY;
      viewport.style.cursor = 'grabbing';
      e.preventDefault();
    });

    window.addEventListener('mousemove', function (e) {
      if (!isDragging) return;
      e.preventDefault();
      currentPanX = e.clientX - dragStartX;
      currentPanY = e.clientY - dragStartY;
      clampPan();
      applyTransform(false);
    });

    window.addEventListener('mouseup', function () {
      if (isDragging) {
        isDragging = false;
        viewport.style.cursor = currentScale > 1.05 ? 'grab' : 'default';
      }
    });

    // Double-click toggle (1x <-> 2.5x)
    viewport.addEventListener('dblclick', function (e) {
      if (currentScale > 1.2) {
        currentScale = 1;
        currentPanX = 0;
        currentPanY = 0;
        applyTransform(true);
      } else {
        const rect = viewport.getBoundingClientRect();
        const focalX = e.clientX - rect.left - rect.width / 2;
        const focalY = e.clientY - rect.top - rect.height / 2;
        zoomBy(1.5, { x: focalX, y: focalY });
      }
    });

    // Mobile / Tablet Touch Pinch & Pan
    let touchStartX = 0;
    let touchStartY = 0;

    viewport.addEventListener('touchstart', function (e) {
      if (e.touches.length === 1) {
        if (currentScale > 1.05) {
          isDragging = true;
          touchStartX = e.touches[0].clientX - currentPanX;
          touchStartY = e.touches[0].clientY - currentPanY;
        }
      } else if (e.touches.length === 2) {
        isDragging = false;
        initialPinchDistance = Math.hypot(
          e.touches[0].clientX - e.touches[1].clientX,
          e.touches[0].clientY - e.touches[1].clientY
        );
        initialPinchScale = currentScale;
      }
    }, { passive: false });

    viewport.addEventListener('touchmove', function (e) {
      if (e.touches.length === 1 && isDragging) {
        e.preventDefault();
        currentPanX = e.touches[0].clientX - touchStartX;
        currentPanY = e.touches[0].clientY - touchStartY;
        clampPan();
        applyTransform(false);
      } else if (e.touches.length === 2 && initialPinchDistance) {
        e.preventDefault();
        const currentDistance = Math.hypot(
          e.touches[0].clientX - e.touches[1].clientX,
          e.touches[0].clientY - e.touches[1].clientY
        );
        const pinchRatio = currentDistance / initialPinchDistance;
        currentScale = Math.min(Math.max(minScale, initialPinchScale * pinchRatio), maxScale);
        clampPan();
        applyTransform(false);
      }
    }, { passive: false });

    viewport.addEventListener('touchend', function (e) {
      if (e.touches.length < 2) {
        initialPinchDistance = null;
      }
      if (e.touches.length === 0) {
        isDragging = false;
        if (currentScale <= 1.05) {
          currentPanX = 0;
          currentPanY = 0;
          applyTransform(true);
        }
      }
    });

    // Keyboard Shortcuts
    window.addEventListener('keydown', function (e) {
      if (!modal.classList.contains('active')) return;

      if (e.key === 'Escape') {
        closeZoomViewer();
      } else if (e.key === '+' || e.key === '=') {
        zoomBy(0.5);
      } else if (e.key === '-' || e.key === '_') {
        zoomBy(-0.5);
      } else if (e.key === '0' || e.key.toLowerCase() === 'r') {
        currentScale = 1;
        currentPanX = 0;
        currentPanY = 0;
        applyTransform(true);
      }
    });
  });
</script>
@endpush
