@extends('layouts.app')

@section('title', 'Alumni Network — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-2">Global & Professional Footprint</div>
      <h1 class="mb-2">Alumni Network</h1>
      <p class="lead mb-0">
        Explore where BUP ICT graduates are making an impact — across global technology hubs,
        industry leaders in Bangladesh, entrepreneurial ventures, and world-class research institutes.
      </p>
    </div>
  </section>

  <!-- Sticky In-Page Navigation Bar -->
  <nav class="sticky-top bg-white border-bottom py-2 shadow-sm" style="top: 72px; z-index: 990;">
    <div class="container">
      <div class="d-flex gap-2 overflow-auto py-1" style="white-space: nowrap; scrollbar-width: thin;">
        <a href="#alumni-abroad" class="btn btn-sm btn-outline-ink d-flex align-items-center gap-2">
          <span>🌍 Alumni Abroad</span>
          <span class="badge bg-brass text-white">{{ $abroad->count() }}</span>
        </a>
        <a href="#professionals-bangladesh" class="btn btn-sm btn-outline-ink d-flex align-items-center gap-2">
          <span>🏢 Professionals in Bangladesh</span>
          <span class="badge bg-brass text-white">{{ $professionals->count() }}</span>
        </a>
        <a href="#entrepreneurs-business" class="btn btn-sm btn-outline-ink d-flex align-items-center gap-2">
          <span>🚀 Entrepreneurs & Business Owners</span>
          <span class="badge bg-brass text-white">{{ $entrepreneurs->count() }}</span>
        </a>
        <a href="#higher-studies-research" class="btn btn-sm btn-outline-ink d-flex align-items-center gap-2">
          <span>🎓 Higher Studies & Research</span>
          <span class="badge bg-brass text-white">{{ $researchers->count() }}</span>
        </a>
      </div>
    </div>
  </nav>

  <div class="container py-5">

    <!-- Section 1: Alumni Abroad -->
    <section id="alumni-abroad" class="network-section mb-5" style="scroll-margin-top: 140px;">
      <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 pb-2 border-bottom">
        <div>
          <div class="section-eyebrow mb-1">Global Community</div>
          <h2 class="h4 mb-1">Alumni Abroad</h2>
          <p class="text-muted-slate small mb-0">Graduates pursuing careers and life across countries worldwide.</p>
        </div>
        <div class="badge bg-light text-dark border small px-3 py-2 mt-2 mt-md-0">
          {{ $abroad->count() }} {{ \Illuminate\Support\Str::plural('Alumnus', $abroad->count()) }}
        </div>
      </div>

      @if($abroad->isEmpty())
        <div class="placeholder-panel py-4">
          <h3 class="h6 mb-2">No alumni abroad listed yet</h3>
          <p class="small text-muted-slate mb-0">Graduates who specify an international location in their profile will appear here.</p>
        </div>
      @else
        <div class="row g-4">
          @foreach($abroad as $alumnus)
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="card-feature h-100 d-flex flex-column text-center p-3">
                <div class="mb-3 d-flex justify-content-center">
                  <div class="position-relative overflow-hidden rounded border shadow-sm mx-auto"
                       style="width: 110px; height: 110px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                    @if($alumnus->profile_photo_url)
                      <img src="{{ $alumnus->profile_photo_url }}"
                           alt="{{ $alumnus->full_name }}"
                           class="w-100 h-100"
                           style="object-fit: cover;"
                           onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate fw-bold fs-3\'>{{ substr($alumnus->full_name, 0, 1) }}</div>';"
                           loading="lazy">
                    @else
                      <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center fw-bold fs-3" style="background-color: var(--parchment-50, #f8f6f1); color: var(--ink-900);">
                        {{ substr($alumnus->full_name, 0, 1) }}
                      </div>
                    @endif
                  </div>
                </div>

                <h3 class="h6 font-display fw-bold mb-1">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="text-decoration-none text-reset">
                    {{ $alumnus->full_name }}
                  </a>
                </h3>

                <div class="small text-muted-slate mb-2">
                  <span>{{ $alumnus->batch?->batch_name ?? $alumnus->batch?->batch_code ?? 'BUP ICT' }}</span>
                  @if($alumnus->graduation_year)
                    <span> &bull; '{{ substr((string)$alumnus->graduation_year, -2) }}</span>
                  @endif
                </div>

                @if($alumnus->country || $alumnus->city)
                  <div class="mb-2">
                    <span class="badge bg-parchment text-brass border small">
                      📍 {{ collect([$alumnus->city, $alumnus->country])->filter()->implode(', ') }}
                    </span>
                  </div>
                @endif

                @if($alumnus->designation || $alumnus->company)
                  <div class="small mb-2 text-truncate" title="{{ $alumnus->designation }} {{ $alumnus->company ? 'at '.$alumnus->company : '' }}">
                    @if($alumnus->designation)
                      <span class="fw-semibold">{{ $alumnus->designation }}</span>
                    @endif
                    @if($alumnus->company)
                      <span class="text-muted-slate">{{ $alumnus->designation ? 'at' : '' }} {{ $alumnus->company }}</span>
                    @endif
                  </div>
                @elseif($alumnus->current_institution)
                  <div class="small mb-2 text-muted-slate text-truncate" title="{{ $alumnus->current_institution }}">
                    🏛️ {{ $alumnus->current_institution }}
                  </div>
                @endif

                @if($alumnus->higher_studies)
                  <div class="small mb-2 text-muted-slate text-truncate" title="{{ $alumnus->higher_studies }}">
                    🎓 {{ $alumnus->higher_studies }}
                  </div>
                @endif

                <div class="mt-auto pt-2">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="btn btn-sm btn-outline-ink w-100">
                    View Profile
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

    <!-- Section 2: Professionals in Bangladesh -->
    <section id="professionals-bangladesh" class="network-section mb-5" style="scroll-margin-top: 140px;">
      <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 pb-2 border-bottom">
        <div>
          <div class="section-eyebrow mb-1">National Impact</div>
          <h2 class="h4 mb-1">Professionals in Bangladesh</h2>
          <p class="text-muted-slate small mb-0">Graduates contributing to technology, industry, and governance in Bangladesh.</p>
        </div>
        <div class="badge bg-light text-dark border small px-3 py-2 mt-2 mt-md-0">
          {{ $professionals->count() }} {{ \Illuminate\Support\Str::plural('Professional', $professionals->count()) }}
        </div>
      </div>

      @if($professionals->isEmpty())
        <div class="placeholder-panel py-4">
          <h3 class="h6 mb-2">No professionals listed yet</h3>
          <p class="small text-muted-slate mb-0">Employed alumni working in Bangladesh will appear here as profiles are updated.</p>
        </div>
      @else
        <div class="row g-4">
          @foreach($professionals as $alumnus)
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="card-feature h-100 d-flex flex-column text-center p-3">
                <div class="mb-3 d-flex justify-content-center">
                  <div class="position-relative overflow-hidden rounded border shadow-sm mx-auto"
                       style="width: 110px; height: 110px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                    @if($alumnus->profile_photo_url)
                      <img src="{{ $alumnus->profile_photo_url }}"
                           alt="{{ $alumnus->full_name }}"
                           class="w-100 h-100"
                           style="object-fit: cover;"
                           onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate fw-bold fs-3\'>{{ substr($alumnus->full_name, 0, 1) }}</div>';"
                           loading="lazy">
                    @else
                      <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center fw-bold fs-3" style="background-color: var(--parchment-50, #f8f6f1); color: var(--ink-900);">
                        {{ substr($alumnus->full_name, 0, 1) }}
                      </div>
                    @endif
                  </div>
                </div>

                <h3 class="h6 font-display fw-bold mb-1">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="text-decoration-none text-reset">
                    {{ $alumnus->full_name }}
                  </a>
                </h3>

                <div class="small text-muted-slate mb-2">
                  <span>{{ $alumnus->batch?->batch_name ?? $alumnus->batch?->batch_code ?? 'BUP ICT' }}</span>
                  @if($alumnus->graduation_year)
                    <span> &bull; '{{ substr((string)$alumnus->graduation_year, -2) }}</span>
                  @endif
                </div>

                @if($alumnus->city || $alumnus->country)
                  <div class="mb-2">
                    <span class="badge bg-light text-muted-slate border small">
                      📍 {{ collect([$alumnus->city, $alumnus->country ?? 'Bangladesh'])->filter()->implode(', ') }}
                    </span>
                  </div>
                @endif

                @if($alumnus->designation || $alumnus->company)
                  <div class="small mb-3 text-truncate" title="{{ $alumnus->designation }} {{ $alumnus->company ? 'at '.$alumnus->company : '' }}">
                    @if($alumnus->designation)
                      <span class="fw-semibold">{{ $alumnus->designation }}</span>
                    @endif
                    @if($alumnus->company)
                      <span class="text-muted-slate">{{ $alumnus->designation ? 'at' : '' }} {{ $alumnus->company }}</span>
                    @endif
                  </div>
                @endif

                <div class="mt-auto pt-2">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="btn btn-sm btn-outline-ink w-100">
                    View Profile
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

    <!-- Section 3: Entrepreneurs & Business Owners -->
    <section id="entrepreneurs-business" class="network-section mb-5" style="scroll-margin-top: 140px;">
      <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 pb-2 border-bottom">
        <div>
          <div class="section-eyebrow mb-1">Innovation & Enterprise</div>
          <h2 class="h4 mb-1">Entrepreneurs & Business Owners</h2>
          <p class="text-muted-slate small mb-0">Graduates founding companies, leading startups, and building ventures.</p>
        </div>
        <div class="badge bg-light text-dark border small px-3 py-2 mt-2 mt-md-0">
          {{ $entrepreneurs->count() }} {{ \Illuminate\Support\Str::plural('Entrepreneur', $entrepreneurs->count()) }}
        </div>
      </div>

      @if($entrepreneurs->isEmpty())
        <div class="placeholder-panel py-4">
          <h3 class="h6 mb-2">No entrepreneurs listed yet</h3>
          <p class="small text-muted-slate mb-0">Alumni who are verified founders, CEOs, or business owners will appear here.</p>
        </div>
      @else
        <div class="row g-4">
          @foreach($entrepreneurs as $alumnus)
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="card-feature h-100 d-flex flex-column text-center p-3">
                <div class="mb-3 d-flex justify-content-center">
                  <div class="position-relative overflow-hidden rounded border shadow-sm mx-auto"
                       style="width: 110px; height: 110px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                    @if($alumnus->profile_photo_url)
                      <img src="{{ $alumnus->profile_photo_url }}"
                           alt="{{ $alumnus->full_name }}"
                           class="w-100 h-100"
                           style="object-fit: cover;"
                           onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate fw-bold fs-3\'>{{ substr($alumnus->full_name, 0, 1) }}</div>';"
                           loading="lazy">
                    @else
                      <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center fw-bold fs-3" style="background-color: var(--parchment-50, #f8f6f1); color: var(--ink-900);">
                        {{ substr($alumnus->full_name, 0, 1) }}
                      </div>
                    @endif
                  </div>
                </div>

                <h3 class="h6 font-display fw-bold mb-1">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="text-decoration-none text-reset">
                    {{ $alumnus->full_name }}
                  </a>
                </h3>

                <div class="small text-muted-slate mb-2">
                  <span>{{ $alumnus->batch?->batch_name ?? $alumnus->batch?->batch_code ?? 'BUP ICT' }}</span>
                  @if($alumnus->graduation_year)
                    <span> &bull; '{{ substr((string)$alumnus->graduation_year, -2) }}</span>
                  @endif
                </div>

                <div class="mb-2">
                  <span class="badge bg-brass text-white small">
                    🚀 {{ $alumnus->designation ?: 'Entrepreneur' }}
                  </span>
                </div>

                @if($alumnus->company)
                  <div class="small fw-semibold mb-2 text-truncate" title="{{ $alumnus->company }}">
                    {{ $alumnus->company }}
                  </div>
                @endif

                @if($alumnus->city || $alumnus->country)
                  <div class="small text-muted-slate mb-3 text-truncate">
                    📍 {{ collect([$alumnus->city, $alumnus->country])->filter()->implode(', ') }}
                  </div>
                @endif

                <div class="mt-auto pt-2">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="btn btn-sm btn-outline-ink w-100">
                    View Profile
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

    <!-- Section 4: Higher Studies & Research -->
    <section id="higher-studies-research" class="network-section mb-5" style="scroll-margin-top: 140px;">
      <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 pb-2 border-bottom">
        <div>
          <div class="section-eyebrow mb-1">Academia & Investigation</div>
          <h2 class="h4 mb-1">Higher Studies & Research</h2>
          <p class="text-muted-slate small mb-0">Alumni engaged in advanced degrees, doctoral studies, and scientific research.</p>
        </div>
        <div class="badge bg-light text-dark border small px-3 py-2 mt-2 mt-md-0">
          {{ $researchers->count() }} {{ \Illuminate\Support\Str::plural('Scholar', $researchers->count()) }}
        </div>
      </div>

      @if($researchers->isEmpty())
        <div class="placeholder-panel py-4">
          <h3 class="h6 mb-2">No higher studies or research records listed yet</h3>
          <p class="small text-muted-slate mb-0">Graduates pursuing graduate studies, research fellowships, or academic appointments will appear here.</p>
        </div>
      @else
        <div class="row g-4">
          @foreach($researchers as $alumnus)
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="card-feature h-100 d-flex flex-column text-center p-3">
                <div class="mb-3 d-flex justify-content-center">
                  <div class="position-relative overflow-hidden rounded border shadow-sm mx-auto"
                       style="width: 110px; height: 110px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                    @if($alumnus->profile_photo_url)
                      <img src="{{ $alumnus->profile_photo_url }}"
                           alt="{{ $alumnus->full_name }}"
                           class="w-100 h-100"
                           style="object-fit: cover;"
                           onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate fw-bold fs-3\'>{{ substr($alumnus->full_name, 0, 1) }}</div>';"
                           loading="lazy">
                    @else
                      <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center fw-bold fs-3" style="background-color: var(--parchment-50, #f8f6f1); color: var(--ink-900);">
                        {{ substr($alumnus->full_name, 0, 1) }}
                      </div>
                    @endif
                  </div>
                </div>

                <h3 class="h6 font-display fw-bold mb-1">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="text-decoration-none text-reset">
                    {{ $alumnus->full_name }}
                  </a>
                </h3>

                <div class="small text-muted-slate mb-2">
                  <span>{{ $alumnus->batch?->batch_name ?? $alumnus->batch?->batch_code ?? 'BUP ICT' }}</span>
                  @if($alumnus->graduation_year)
                    <span> &bull; '{{ substr((string)$alumnus->graduation_year, -2) }}</span>
                  @endif
                </div>

                @if($alumnus->higher_studies)
                  <div class="mb-2">
                    <span class="badge bg-parchment text-ink border small text-truncate" style="max-width: 100%;" title="{{ $alumnus->higher_studies }}">
                      📚 {{ $alumnus->higher_studies }}
                    </span>
                  </div>
                @endif

                @if($alumnus->current_institution)
                  <div class="small fw-semibold mb-1 text-truncate" title="{{ $alumnus->current_institution }}">
                    🏛️ {{ $alumnus->current_institution }}
                  </div>
                @endif

                @if($alumnus->designation)
                  <div class="small text-muted-slate mb-1 text-truncate" title="{{ $alumnus->designation }}">
                    {{ $alumnus->designation }}
                  </div>
                @endif

                @if($alumnus->company)
                  <div class="small text-muted-slate mb-2 text-truncate" title="{{ $alumnus->company }}">
                    🏢 {{ $alumnus->company }}
                  </div>
                @endif

                @if($alumnus->city || $alumnus->country)
                  <div class="small text-muted-slate mb-3 text-truncate">
                    📍 {{ collect([$alumnus->city, $alumnus->country])->filter()->implode(', ') }}
                  </div>
                @endif

                <div class="mt-auto pt-2">
                  <a href="{{ route('alumni.show', $alumnus) }}" class="btn btn-sm btn-outline-ink w-100">
                    View Profile
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

  </div>
@endsection
