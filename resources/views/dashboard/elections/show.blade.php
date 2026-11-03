@extends('layouts.dashboard')

@section('title', $election->title . ' — Cast Vote')
@section('page_title', 'Cast Your Vote')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger small mb-4">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h5 mb-0 font-display">{{ $election->title }}</h2>
      <span class="badge bg-primary">Online Ballot</span>
    </div>
    <p class="small text-muted-slate mb-2">
      Term: <strong>{{ $election->term }}</strong> | Voting Window: <strong>{{ $election->start_time->format('M j, Y g:i A') }}</strong> to <strong>{{ $election->end_time->format('M j, Y g:i A') }}</strong>
    </p>
    @if($election->description)
      <p class="small text-muted-slate mb-0 border-top pt-2">{{ $election->description }}</p>
    @endif
  </div>

  @if(! $election->isOpenForVoting())
    <div class="alert alert-warning small mb-4">
      Voting is currently closed for this election. You can review the positions and candidate manifestos below.
    </div>
  @endif

  @if(! $isEligible)
    <div class="alert alert-info small mb-4">
      <strong>Preview Mode:</strong> Your account is not currently linked to an active, verified alumni profile. You can review positions and candidate statements below, but you cannot cast a vote.
    </div>
  @endif

  <!-- Positions & Candidate Voting Cards -->
  <div class="row g-4">
    @forelse($election->positions as $position)
      @php
        $hasVoted = in_array($position->id, $votedPositionIds, true);
      @endphp
      <div class="col-12">
        <div class="card-feature border {{ $hasVoted ? 'border-success bg-light' : '' }}">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
              <h3 class="h6 mb-0 font-display">{{ $position->name }}</h3>
              <span class="small text-muted-slate">{{ $position->candidates->count() }} candidates running</span>
            </div>
            @if($hasVoted)
              <span class="badge bg-success">✓ You have voted for this position</span>
            @elseif(! $isEligible)
              <span class="badge bg-secondary">Ineligible to Vote</span>
            @elseif($election->isOpenForVoting())
              <span class="badge bg-warning text-dark">Vote Pending</span>
            @else
              <span class="badge bg-secondary">Voting Closed</span>
            @endif
          </div>

          @if($position->candidates->isEmpty())
            <p class="small text-muted-slate mb-0">No candidates registered for this position.</p>
          @else
            @if($hasVoted || ! $election->isOpenForVoting() || ! $isEligible)
              <!-- Read-only Candidate List -->
              <div class="row g-3">
                @foreach($position->candidates as $candidate)
                  <div class="col-md-6">
                    <div class="border rounded p-3 bg-white h-100">
                      <div class="d-flex align-items-center gap-3 mb-2">
                        @if($candidate->photo_url)
                          <img src="{{ $candidate->photo_url }}" alt="{{ $candidate->alumni->name }}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                        @else
                          <div class="rounded-circle bg-parchment d-flex align-items-center justify-content-center text-muted-slate fw-bold" style="width: 48px; height: 48px; font-size: 16px;">
                            {{ substr($candidate->alumni->name, 0, 1) }}
                          </div>
                        @endif
                        <div>
                          <div class="fw-semibold">{{ $candidate->alumni->name }}</div>
                          <div class="small text-muted-slate">Batch: {{ $candidate->alumni->batch?->name ?? '—' }}</div>
                        </div>
                      </div>
                      @if($candidate->statement)
                        <div class="small text-muted border-top pt-2 mt-2">
                          "{{ $candidate->statement }}"
                        </div>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <!-- Active Voting Form for this position -->
              <form action="{{ route('dashboard.elections.vote', $election) }}" method="POST">
                @csrf
                <input type="hidden" name="position_id" value="{{ $position->id }}">

                <div class="row g-3 mb-3">
                  @foreach($position->candidates as $candidate)
                    <div class="col-md-6">
                      <label class="border rounded p-3 bg-white h-100 w-100 d-block cursor-pointer position-relative shadow-sm" for="cand_{{ $candidate->id }}">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="candidate_id" id="cand_{{ $candidate->id }}" value="{{ $candidate->id }}" required>
                          <div class="ms-2">
                            <div class="d-flex align-items-center gap-2 mb-2">
                              @if($candidate->photo_url)
                                <img src="{{ $candidate->photo_url }}" alt="{{ $candidate->alumni->name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                              @else
                                <div class="rounded-circle bg-parchment d-flex align-items-center justify-content-center text-muted-slate fw-bold" style="width: 40px; height: 40px; font-size: 14px;">
                                  {{ substr($candidate->alumni->name, 0, 1) }}
                                </div>
                              @endif
                              <div>
                                <div class="fw-semibold">{{ $candidate->alumni->name }}</div>
                                <div class="small text-muted-slate">Batch: {{ $candidate->alumni->batch?->name ?? '—' }}</div>
                              </div>
                            </div>
                            @if($candidate->statement)
                              <div class="small text-muted border-top pt-2 mt-1">
                                "{{ $candidate->statement }}"
                              </div>
                            @endif
                          </div>
                        </div>
                      </label>
                    </div>
                  @endforeach
                </div>

                <div class="text-end">
                  <button type="submit" class="btn btn-brass btn-sm">
                    Submit Vote for {{ $position->name }}
                  </button>
                </div>
              </form>
            @endif
          @endif
        </div>
      </div>
    @empty
      <div class="col-12">
        <p class="small text-muted-slate">No positions found for this election.</p>
      </div>
    @endforelse
  </div>
@endsection
