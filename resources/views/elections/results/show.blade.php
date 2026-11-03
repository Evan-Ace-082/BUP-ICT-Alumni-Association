@extends('layouts.app')

@section('title', $election->title . ' — Certified Results')

@section('content')
  <div class="py-5 bg-parchment border-bottom">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-brass text-dark">Certified Final Results</span>
            <span class="badge {{ $election->isOnline() ? 'bg-primary' : 'bg-secondary' }}">
              {{ ucfirst($election->type) }}
            </span>
          </div>
          <h1 class="display-6 font-display fw-bold mb-1">{{ $election->title }}</h1>
          <p class="text-muted-slate mb-0 lead small">
            Executive Committee Term: <strong>{{ $election->term }}</strong> | Finalized on <strong>{{ $election->finalized_at?->format('F j, Y') }}</strong>
          </p>
        </div>
        <div>
          <a href="{{ route('elections.results.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Results Archive</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <div class="row g-4">
      @foreach($election->positions as $position)
        @php
          $winner = $position->candidates->firstWhere('is_winner', true);
          $otherCandidates = $position->candidates->where('is_winner', false);
        @endphp
        <div class="col-md-6">
          <div class="card-feature h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
              <h2 class="h6 mb-0 font-display">{{ $position->name }}</h2>
              @if($position->isTieResolved())
                <span class="badge bg-info text-dark">Tie-Break Resolved</span>
              @else
                <span class="badge bg-success">Elected Representative</span>
              @endif
            </div>

            <!-- Winning Candidate Box -->
            @if($winner)
              <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded border {{ $position->isTieResolved() ? 'border-info' : 'border-success' }}">
                @if($winner->photo_url)
                  <img src="{{ $winner->photo_url }}" alt="{{ $winner->alumni->name }}" class="rounded-circle border" style="width: 56px; height: 56px; object-fit: cover;">
                @else
                  <div class="rounded-circle bg-brass d-flex align-items-center justify-content-center text-dark fw-bold fs-5" style="width: 56px; height: 56px;">
                    {{ substr($winner->alumni->name, 0, 1) }}
                  </div>
                @endif
                <div class="overflow-hidden flex-grow-1">
                  <div class="d-flex align-items-center gap-2">
                    <h3 class="h6 mb-0 fw-bold">
                      <a href="{{ route('alumni.show', $winner->alumni) }}" class="text-decoration-none">
                        {{ $winner->alumni->name }}
                      </a>
                    </h3>
                    @if($position->isTieResolved())
                      <span class="badge bg-info text-dark small">Tie-Break Winner</span>
                    @else
                      <span class="badge bg-success small">Winner</span>
                    @endif
                  </div>
                  <div class="small text-muted-slate">
                    Batch: {{ $winner->alumni->batch?->name ?? '—' }} | Student ID: {{ $winner->alumni->student_id }}
                  </div>
                  @if($election->isOnline())
                    <div class="small fw-semibold text-brass mt-1">
                      Received {{ $winner->votes_count }} votes
                      @if($position->isTieResolved())
                        (Tied for top vote count)
                      @else
                        (Highest vote count)
                      @endif
                    </div>
                  @endif
                  @if($position->isTieResolved())
                    <div class="mt-2 pt-2 border-top small text-muted-slate">
                      <span class="badge bg-warning text-dark me-1">Admin-Resolved Tie</span>
                      Method: <strong>{{ $position->tie_resolution_method_label }}</strong>
                      @if($position->tie_resolution_reason)
                        <br><span class="fst-italic text-dark">Reason: "{{ $position->tie_resolution_reason }}"</span>
                      @endif
                      <br><span class="text-secondary">Resolved by {{ $position->tieResolvedBy?->name ?? 'Administrator' }} on {{ $position->tie_resolved_at?->format('M d, Y H:i') }}</span>
                    </div>
                  @endif
                </div>
              </div>
            @else
              <p class="small text-muted-slate">No winner recorded for this position.</p>
            @endif

            <!-- Runner-ups / Other Candidates (aggregate counts only) -->
            @if($otherCandidates->isNotEmpty())
              <div class="mt-3 pt-2">
                <h4 class="small text-uppercase text-muted-slate fw-semibold mb-2" style="font-size: 11px; letter-spacing: .06em;">Other Candidates</h4>
                <ul class="list-unstyled mb-0 small">
                  @foreach($otherCandidates as $candidate)
                    <li class="d-flex justify-content-between align-items-center py-1 border-bottom-dashed">
                      <a href="{{ route('alumni.show', $candidate->alumni) }}" class="text-decoration-none text-muted-slate">
                        {{ $candidate->alumni->name }} ({{ $candidate->alumni->batch?->name ?? '—' }})
                      </a>
                      @if($election->isOnline())
                        <span class="badge bg-light text-dark border">{{ $candidate->votes_count }} votes</span>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endsection
