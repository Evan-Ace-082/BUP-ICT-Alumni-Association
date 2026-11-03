@extends('layouts.dashboard')

@section('title', 'Elections & Voting — BUP ICT Alumni Association')
@section('page_title', 'Elections & Voting')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger small mb-4">{{ session('error') }}</div>
  @endif

  <div class="mb-4">
    <h2 class="h5 mb-1 font-display">Active & Upcoming Elections</h2>
    <p class="small text-muted-slate mb-0">Cast your vote securely for candidate representatives of the BUP ICT Alumni Association.</p>
  </div>

  @if($ongoingElections->isEmpty() && $upcomingElections->isEmpty() && $pastElections->isEmpty())
    <div class="card-feature text-center py-5">
      <div class="py-3">
        <span class="fs-1 d-block mb-2">🗳️</span>
        <h3 class="h6 font-display mb-2">No Elections Available</h3>
        <p class="small text-muted-slate mb-0">No elections are currently scheduled or available. When an election is initiated by the administration, it will appear here for eligible alumni.</p>
      </div>
    </div>
  @else
    <!-- Active Online Elections -->
    <div class="card-feature mb-4">
      <h3 class="h6 mb-3 font-display">Ongoing Online Elections</h3>

      @if($ongoingElections->isEmpty())
        <div class="placeholder-panel text-center py-4">
          <p class="small text-muted-slate mb-0">There are no active online elections taking place at this time.</p>
        </div>
      @else
        <div class="row g-3">
          @foreach($ongoingElections as $election)
            <div class="col-md-6">
              <div class="border rounded p-3 bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    @if($election->isOpenForVoting())
                      <span class="badge bg-success">Voting Open</span>
                    @elseif(now()->lt($election->start_time))
                      <span class="badge bg-info text-dark">Opens Soon</span>
                    @else
                      <span class="badge bg-secondary">Voting Closed</span>
                    @endif
                    <span class="small text-muted-slate">Term: {{ $election->term }}</span>
                  </div>
                  <h4 class="h6 font-display mb-1">{{ $election->title }}</h4>
                  <p class="small text-muted-slate mb-3">
                    {{ Str::limit($election->description, 100) }}
                  </p>
                  <div class="small text-muted-slate mb-2">
                    Voting ends: <strong>{{ $election->end_time->format('M j, Y g:i A') }}</strong>
                  </div>
                  <div class="small text-muted-slate mb-3">
                    Positions to elect: <strong>{{ $election->positions->count() }}</strong>
                  </div>
                </div>
                <div>
                  @if($election->isOpenForVoting())
                    <a href="{{ route('dashboard.elections.show', $election) }}" class="btn btn-brass btn-sm w-100">
                      Cast Your Vote →
                    </a>
                  @else
                    <a href="{{ route('dashboard.elections.show', $election) }}" class="btn btn-outline-ink btn-sm w-100">
                      View Positions & Candidates →
                    </a>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <!-- Upcoming Online Elections -->
    @if($upcomingElections->isNotEmpty())
      <div class="card-feature mb-4">
        <h3 class="h6 mb-3 font-display">Upcoming Online Elections</h3>
        <div class="row g-3">
          @foreach($upcomingElections as $election)
            <div class="col-md-6">
              <div class="border rounded p-3 bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary">Scheduled</span>
                    <span class="small text-muted-slate">Term: {{ $election->term }}</span>
                  </div>
                  <h4 class="h6 font-display mb-1">{{ $election->title }}</h4>
                  <p class="small text-muted-slate mb-3">
                    {{ Str::limit($election->description, 100) }}
                  </p>
                  <div class="small text-muted-slate mb-2">
                    Voting opens: <strong>{{ $election->start_time->format('M j, Y g:i A') }}</strong>
                  </div>
                  <div class="small text-muted-slate mb-3">
                    Positions to elect: <strong>{{ $election->positions->count() }}</strong>
                  </div>
                </div>
                <div>
                  <a href="{{ route('dashboard.elections.show', $election) }}" class="btn btn-outline-ink btn-sm w-100">
                    Preview Candidates →
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <!-- Past Elections -->
    <div class="card-feature">
      <h3 class="h6 mb-3 font-display">Past Elections</h3>

      @if($pastElections->isEmpty())
        <p class="small text-muted-slate mb-0">No past elections recorded.</p>
      @else
        <div class="table-responsive">
          <table class="table table-hover align-middle small mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Term</th>
                <th>Status</th>
                <th>Concluded</th>
                <th class="text-end">Results</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pastElections as $election)
                <tr>
                  <td class="fw-semibold">{{ $election->title }}</td>
                  <td>{{ $election->term }}</td>
                  <td>
                    <span class="badge {{ $election->isFinalized() ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                      {{ ucfirst($election->status) }}
                    </span>
                  </td>
                  <td class="text-muted-slate">{{ $election->end_time->format('M j, Y') }}</td>
                  <td class="text-end">
                    @if($election->isFinalized())
                      <a href="{{ route('elections.results.show', $election) }}" target="_blank" class="btn btn-outline-ink btn-sm">Public Results ↗</a>
                    @else
                      <span class="small text-muted-slate">Awaiting Finalization</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $pastElections->links() }}
        </div>
      @endif
    </div>
  @endif
@endsection
