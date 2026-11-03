@extends('layouts.dashboard')

@section('title', 'Executive Committees — BUP ICT Alumni Association')
@section('page_title', 'Executive Committees')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h5 mb-1 font-display">Executive Committees</h2>
      <p class="small text-muted-slate mb-0">Manage current leadership and preserve historical committees.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('committees.index') }}" target="_blank" class="btn btn-outline-ink btn-sm">Public View ↗</a>
      <a href="{{ route('admin.committees.create') }}" class="btn btn-brass btn-sm">+ Create Committee Manually</a>
    </div>
  </div>

  <!-- Current Committee Card -->
  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="h6 mb-0 font-display">Current Executive Committee</h3>
      @if($currentCommittee)
        <span class="badge bg-success">Active / Current</span>
      @endif
    </div>

    @if(! $currentCommittee)
      <div class="placeholder-panel text-center py-4">
        <p class="small text-muted-slate mb-2">No active committee is currently marked as current.</p>
        <p class="small text-muted-slate mb-0">Finalizing an election or manually activating a committee will display it here.</p>
      </div>
    @else
      <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
        <div>
          <h4 class="h6 mb-1 fw-bold">Term: {{ $currentCommittee->term }}</h4>
          <span class="small text-muted-slate">
            Origin: {{ $currentCommittee->election ? "Elected in '{$currentCommittee->election->title}'" : 'Manual Creation' }}
            | Members: {{ $currentCommittee->members->count() }}
          </span>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('admin.committees.show', $currentCommittee) }}" class="btn btn-brass btn-sm">Manage Members</a>
          <a href="{{ route('admin.committees.edit', $currentCommittee) }}" class="btn btn-outline-ink btn-sm">Edit Term</a>
        </div>
      </div>

      <div class="row g-3">
        @forelse($currentCommittee->members as $member)
          <div class="col-md-6 col-lg-4">
            <div class="border rounded p-2 d-flex align-items-center gap-2 bg-light">
              <div class="rounded-circle bg-parchment d-flex align-items-center justify-content-center text-muted-slate fw-bold" style="width: 38px; height: 38px; font-size: 13px;">
                {{ substr($member->alumni->full_name, 0, 1) }}
              </div>
              <div class="overflow-hidden">
                <div class="fw-semibold small text-truncate">{{ $member->alumni->full_name }}</div>
                <div class="text-brass small" style="font-size: 12px;">{{ $member->position_name }}</div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <p class="small text-muted-slate mb-0">No members listed in this committee yet. Click "Manage Members" to add.</p>
          </div>
        @endforelse
      </div>
    @endif
  </div>

  <!-- Historical Committees -->
  <div class="card-feature">
    <h3 class="h6 mb-3 font-display">Previous / Historical Committees</h3>

    @if($historicalCommittees->isEmpty())
      <p class="small text-muted-slate mb-0">No historical committees recorded yet.</p>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead>
            <tr>
              <th>Term</th>
              <th>Origin</th>
              <th>Member Count</th>
              <th>Created At</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($historicalCommittees as $comm)
              <tr>
                <td>
                  <strong>{{ $comm->term }}</strong>
                </td>
                <td>
                  {{ $comm->election ? "Elected in '{$comm->election->title}'" : 'Manual' }}
                </td>
                <td>{{ $comm->members->count() }} members</td>
                <td>{{ $comm->created_at->format('M j, Y') }}</td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <form action="{{ route('admin.committees.activate', $comm) }}" method="POST" class="d-inline" onsubmit="return confirm('Set committee {{ $comm->term }} as the active current committee?');">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-outline-success btn-sm">Set as Current</button>
                    </form>
                    <a href="{{ route('admin.committees.show', $comm) }}" class="btn btn-outline-ink btn-sm">View / Manage</a>
                    <form action="{{ route('admin.committees.destroy', $comm) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this historical committee record?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        {{ $historicalCommittees->links() }}
      </div>
    @endif
  </div>
@endsection
