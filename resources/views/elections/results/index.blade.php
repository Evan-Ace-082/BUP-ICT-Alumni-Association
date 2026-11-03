@extends('layouts.app')

@section('title', 'Election Results — BUP ICT Alumni Association')

@section('content')
  <div class="py-5 bg-parchment border-bottom">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <span class="badge bg-brass text-dark px-3 py-2 text-uppercase mb-2" style="letter-spacing: .08em; font-size: 11px;">Elections Archive</span>
          <h1 class="display-6 font-display fw-bold mb-2">Official Election Results</h1>
          <p class="text-muted-slate mb-0 lead small">
            Transparent and certified election results for the BUP ICT Alumni Association Executive Committees.
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">
    @if($finalizedElections->isEmpty())
      <div class="card-feature text-center py-5">
        <h2 class="h5 font-display mb-2">No finalized election results yet</h2>
        <p class="small text-muted-slate mb-0">Official election results will appear here once finalized by the association administrators.</p>
      </div>
    @else
      <div class="row g-4">
        @foreach($finalizedElections as $election)
          <div class="col-md-6 col-lg-4">
            <div class="card-feature h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-brass text-dark">Term: {{ $election->term }}</span>
                  <span class="badge {{ $election->isOnline() ? 'bg-primary' : 'bg-secondary' }}">
                    {{ ucfirst($election->type) }}
                  </span>
                </div>
                <h3 class="h6 font-display mb-2">
                  <a href="{{ route('elections.results.show', $election) }}" class="text-decoration-none">
                    {{ $election->title }}
                  </a>
                </h3>
                <p class="small text-muted-slate mb-3">
                  Finalized on {{ $election->finalized_at?->format('M j, Y') ?? '—' }}
                </p>
                <div class="small text-muted-slate mb-3">
                  <strong>{{ $election->positions->count() }}</strong> elected executive positions
                </div>
              </div>

              <div>
                <a href="{{ route('elections.results.show', $election) }}" class="btn btn-outline-ink btn-sm w-100">
                  View Certified Results →
                </a>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-4">
        {{ $finalizedElections->links() }}
      </div>
    @endif
  </div>
@endsection
