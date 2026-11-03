@extends('layouts.app')

@section('title', 'Executive Committee — BUP ICT Alumni Association')

@section('content')
  <!-- Hero Section -->
  <div class="py-5 bg-parchment border-bottom">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <span class="badge bg-brass text-dark px-3 py-2 text-uppercase mb-2" style="letter-spacing: .08em; font-size: 11px;">Leadership</span>
          <h1 class="display-6 font-display fw-bold mb-2">Executive Committee</h1>
          <p class="text-muted-slate mb-0 lead small">
            Meet the leaders serving and guiding the BUP ICT Alumni Association community.
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('committees.history') }}" class="btn btn-outline-ink btn-sm">📜 Past Committees History</a>
          <a href="{{ route('elections.results.index') }}" class="btn btn-outline-ink btn-sm">Election Results</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">
    @if(! $currentCommittee)
      <div class="card-feature text-center py-5">
        <h2 class="h5 font-display mb-2">Executive Committee in Formation</h2>
        <p class="small text-muted-slate mb-3">The current executive committee has not been designated yet.</p>
        <a href="{{ route('committees.history') }}" class="btn btn-outline-ink btn-sm">View Previous Committees</a>
      </div>
    @else
      <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="h4 font-display mb-1">Current Committee — Term {{ $currentCommittee->term }}</h2>
          <span class="small text-muted-slate">
            {{ $currentCommittee->election ? "Elected in {$currentCommittee->election->title}" : 'Official Executive Committee' }}
          </span>
        </div>
        @if($currentCommittee->election)
          <a href="{{ route('elections.results.show', $currentCommittee->election) }}" class="btn btn-outline-ink btn-sm">
            View Originating Election Results ↗
          </a>
        @endif
      </div>

      <div class="row g-4">
        @forelse($currentCommittee->members as $member)
          <div class="col-md-6 col-lg-4">
            <div class="card-feature h-100 d-flex flex-column text-center p-4">
              <div class="mb-3 d-flex justify-content-center">
                <div class="position-relative overflow-hidden rounded border shadow-sm mx-auto"
                     style="width: 140px; height: 140px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                  @if($member->photo_url)
                    <img src="{{ $member->photo_url }}"
                         alt="{{ $member->alumni->full_name }}"
                         class="w-100 h-100"
                         style="object-fit: cover;"
                         onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate fw-bold fs-3\'>{{ substr($member->alumni->full_name, 0, 1) }}</div>';"
                         loading="lazy">
                  @else
                    <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center fw-bold fs-2" style="background-color: var(--parchment-50, #f8f6f1); color: var(--ink-900);">
                      {{ substr($member->alumni->full_name, 0, 1) }}
                    </div>
                  @endif
                </div>
              </div>

              <h3 class="h6 font-display fw-bold mb-1">
                <a href="{{ route('alumni.show', $member->alumni) }}" class="text-decoration-none">
                  {{ $member->alumni->full_name }}
                </a>
              </h3>

              <div class="badge bg-brass text-white px-3 py-1 mb-2 font-display" style="font-size: 12px; letter-spacing: 0.02em;">
                {{ $member->position_name }}
              </div>

              <div class="small text-muted-slate mb-3">
                <div>Batch: {{ $member->alumni->batch?->batch_name ?? $member->alumni->batch?->name ?? '—' }}</div>
                @if($member->alumni->designation || $member->alumni->company)
                  <div>
                    {{ $member->alumni->designation }}
                    @if($member->alumni->designation && $member->alumni->company) at @endif
                    {{ $member->alumni->company }}
                  </div>
                @endif
              </div>

              <div class="mt-auto">
                <a href="{{ route('alumni.show', $member->alumni) }}" class="btn btn-outline-ink btn-sm">
                  View Profile
                </a>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <p class="small text-muted-slate">Members for this committee have not yet been populated.</p>
          </div>
        @endforelse
      </div>
    @endif
  </div>
@endsection
