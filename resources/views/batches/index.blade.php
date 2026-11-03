@extends('layouts.app')

@section('title', 'Batches — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">Batches</div>
      <h1 class="mb-0">Every generation, one list</h1>
      <p class="text-muted-slate mt-2 mb-0">Explore our alumni community across all BUP ICT batches.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="row g-3">
        @forelse ($batches as $batch)
          <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('batches.show', $batch) }}" class="text-decoration-none text-reset d-block h-100">
              <div class="card-feature h-100 d-flex flex-column justify-content-between p-3 position-relative">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-badge">{{ $batch->ict_name }}</div>
                    <span class="badge bg-light text-brass border small">{{ $batch->batch_code }}</span>
                  </div>
                  <h2 class="h6 mb-1 text-ink fw-bold">{{ $batch->ict_name }}</h2>
                  <p class="small text-muted-slate mb-1">
                    <strong>{{ $batch->alumni_count }}</strong> {{ \Illuminate\Support\Str::plural('Alumnus', $batch->alumni_count) }}
                  </p>
                  @if ($batch->session)
                    <p class="small text-muted-slate mb-0">Session: {{ $batch->session }}</p>
                  @endif
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                  <span class="small text-brass fw-semibold">View Alumni</span>
                  <span class="small text-brass">&rarr;</span>
                </div>
              </div>
            </a>
          </div>
        @empty
          <div class="col-12">
            <div class="placeholder-panel">No batches have been added yet.</div>
          </div>
        @endforelse
      </div>
    </div>
  </section>
@endsection
