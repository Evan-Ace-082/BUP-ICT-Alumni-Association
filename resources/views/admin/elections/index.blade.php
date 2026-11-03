@extends('layouts.dashboard')

@section('title', 'Elections Management — BUP ICT Alumni Association')
@section('page_title', 'Elections Management')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger small mb-4">{{ session('error') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h2 class="h5 mb-1 font-display">Association Elections</h2>
      <p class="small text-muted-slate mb-0">Oversee online and physical executive committee elections.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="btn-group btn-group-sm" role="group" aria-label="Election filters">
        <a href="{{ route('admin.elections.index', ['filter' => 'active']) }}" class="btn {{ ($filter ?? 'active') === 'active' ? 'btn-brass' : 'btn-outline-ink' }}">
          Active ({{ $activeCount ?? 0 }})
        </a>
        <a href="{{ route('admin.elections.index', ['filter' => 'archived']) }}" class="btn {{ ($filter ?? '') === 'archived' ? 'btn-brass' : 'btn-outline-ink' }}">
          Archived ({{ $archivedCount ?? 0 }})
        </a>
        <a href="{{ route('admin.elections.index', ['filter' => 'all']) }}" class="btn {{ ($filter ?? '') === 'all' ? 'btn-brass' : 'btn-outline-ink' }}">
          All ({{ $totalCount ?? 0 }})
        </a>
      </div>
      <a href="{{ route('admin.elections.create') }}" class="btn btn-brass btn-sm">+ Create New Election</a>
    </div>
  </div>

  <div class="card-feature">
    @if($elections->isEmpty())
      <div class="placeholder-panel text-center py-5">
        <h3 class="h6 mb-2">{{ ($filter ?? '') === 'archived' ? 'No archived elections found' : 'No elections recorded yet' }}</h3>
        <p class="small text-muted-slate mb-3">
          {{ ($filter ?? '') === 'archived' ? 'Elections that have been archived will appear here.' : 'Get started by creating a draft election and adding positions.' }}
        </p>
        @if(($filter ?? '') === 'archived')
          <a href="{{ route('admin.elections.index', ['filter' => 'active']) }}" class="btn btn-outline-ink btn-sm">View Active Elections</a>
        @else
          <a href="{{ route('admin.elections.create') }}" class="btn btn-brass btn-sm">Create Election</a>
        @endif
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead>
            <tr>
              <th>Title & Term</th>
              <th>Type</th>
              <th>Status</th>
              <th>Voting Window</th>
              <th>Positions / Candidates</th>
              <th>Votes</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($elections as $election)
              <tr>
                <td>
                  <a href="{{ route('admin.elections.show', $election) }}" class="fw-semibold text-decoration-none">
                    {{ $election->title }}
                  </a>
                  <div class="text-muted-slate">Term: {{ $election->term }}</div>
                </td>
                <td>
                  <span class="badge {{ $election->isOnline() ? 'bg-primary' : 'bg-secondary' }}">
                    {{ ucfirst($election->type) }}
                  </span>
                </td>
                <td>
                  @if($election->isDraft())
                    <span class="badge bg-secondary">Draft</span>
                  @elseif($election->isOngoing())
                    <span class="badge bg-success">Ongoing</span>
                  @elseif($election->isClosed())
                    <span class="badge bg-warning text-dark">Closed</span>
                  @elseif($election->isFinalized())
                    <span class="badge bg-info text-dark">Finalized</span>
                  @endif
                  @if($election->isArchived())
                    <span class="badge bg-dark ms-1">Archived</span>
                  @endif
                </td>
                <td>
                  <div>{{ $election->start_time->format('M j, Y g:i A') }}</div>
                  <div class="text-muted-slate">to {{ $election->end_time->format('M j, Y g:i A') }}</div>
                </td>
                <td>
                  {{ $election->positions_count }} positions / {{ $election->candidates_count }} candidates
                </td>
                <td>
                  {{ $election->isOnline() ? $election->votes_count : '— (Physical)' }}
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                    <a href="{{ route('admin.elections.show', $election) }}" class="btn btn-outline-ink btn-sm">Manage</a>
                    <a href="{{ route('admin.elections.edit', $election) }}" class="btn btn-outline-ink btn-sm">Edit</a>

                    @if($election->isArchived())
                      <form action="{{ route('admin.elections.unarchive', $election) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore \'{{ $election->title }}\' to active election listings?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-ink btn-sm">Unarchive</button>
                      </form>
                    @elseif(! $election->isOngoing())
                      <form action="{{ route('admin.elections.archive', $election) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive \'{{ $election->title }}\'? It will be hidden from the active list while preserving all vote records and results.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-ink btn-sm">Archive</button>
                      </form>
                    @endif

                    @php
                      $canDelete = $election->canBeDeleted();
                      $deleteNotice = $canDelete
                          ? "Are you sure you want to permanently delete this election? This action cannot be undone."
                          : ($election->votes_count > 0
                              ? "Notice: This election contains {$election->votes_count} recorded votes and cannot be deleted. Deletion is blocked to protect voter integrity."
                              : ($election->isFinalized()
                                  ? "Notice: This election is finalized with certified results and cannot be deleted."
                                  : ($election->isOngoing()
                                      ? "Notice: This election is currently ongoing and cannot be deleted."
                                      : "Notice: An Executive Committee is linked to this election; deletion is blocked.")));
                    @endphp

                    <form action="{{ route('admin.elections.destroy', $election) }}" method="POST" onsubmit="return confirm('{{ $deleteNotice }}');" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ ! $canDelete ? $deleteNotice : 'Permanently delete this election' }}">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        {{ $elections->links() }}
      </div>
    @endif
  </div>
@endsection
