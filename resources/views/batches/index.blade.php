@extends('layouts.app')

@section('title', 'Batches — BUP ICT Alumni Association')

@section('content')
  {{-- Compact Hero Header --}}
  <section class="hero hero-compact">
    <div class="container">
      <div class="eyebrow mb-2">Batches</div>
      <h1 class="mb-1">Every generation, one list</h1>
      <p class="text-muted-glass small mb-0">Explore our alumni community across all BUP ICT batches.</p>
    </div>
  </section>

  <section class="section py-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small text-muted-slate">All graduating cohorts &middot; <strong>{{ $batches->count() }}</strong> active generations</span>
      </div>

      <div class="row g-3">
        @forelse ($batches as $batch)
          <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('batches.show', $batch) }}" class="text-decoration-none text-reset d-block h-100">
              <div class="card-feature h-100 d-flex flex-column justify-content-between p-3 position-relative {{ $batch->alumni_count == 0 ? 'opacity-90' : '' }}">
                <div>
                  {{-- Top metadata: Batch Code and Alumni Count --}}
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-ink text-white font-mono px-2 py-1">{{ $batch->batch_code }}</span>
                    <span class="badge bg-parchment text-brass border small">
                      <strong>{{ $batch->alumni_count }}</strong> {{ \Illuminate\Support\Str::plural('Alumnus', $batch->alumni_count) }}
                    </span>
                  </div>

                  {{-- Clear single Batch Name --}}
                  <h2 class="h5 mb-1 text-ink fw-bold">{{ $batch->ict_name }}</h2>
                  
                  @if ($batch->session)
                    <p class="small text-muted-slate mb-1">Session: {{ $batch->session }}</p>
                  @endif

                  @if ($batch->alumni_count == 0)
                    <div class="mt-2">
                      <span class="badge bg-light text-muted-slate border small font-mono">Pioneer batch &middot; Be the first</span>
                    </div>
                  @endif
                </div>

                {{-- Action link at bottom --}}
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                  <span class="small text-brass fw-semibold">View Batch Roster</span>
                  <span class="small text-brass">&rarr;</span>
                </div>
              </div>
            </a>
          </div>
        @empty
          <div class="col-12">
            <div class="placeholder-panel py-5 text-center">
              <div class="empty-state-icon mx-auto mb-3">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
              </div>
              <h2 class="h6 mb-2 fw-semibold">No batches have been added yet.</h2>
              <p class="small text-muted-slate mb-0">Official academic cohorts will appear here once seeded into the association registry.</p>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </section>
@endsection
