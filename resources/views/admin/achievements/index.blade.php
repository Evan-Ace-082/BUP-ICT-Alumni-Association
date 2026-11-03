@extends('layouts.dashboard')

@section('title', 'Achievements — BUP ICT Alumni Association')
@section('page_title', 'Achievements')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="card-feature p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 pb-2 border-bottom">
      <div>
        <h2 class="h5 mb-1">Achievement Submissions</h2>
        <p class="small text-muted mb-0">Review, approve, or reject alumni achievement submissions.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.achievement-categories.index') }}" class="btn btn-sm btn-outline-secondary">
          Manage Categories & Sectors
        </a>
      </div>
    </div>

    @if ($achievements->isEmpty())
      <div class="placeholder-panel p-5 text-center">
        <h3 class="h6 mb-2">No achievements submitted yet</h3>
        <p class="small text-muted mb-0">Alumni submissions will appear here for administrator review and publication.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover table-sm small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col">Alumnus</th>
              <th scope="col">Title</th>
              <th scope="col">Sector / Category</th>
              <th scope="col">Organization</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($achievements as $achievement)
              @php
                $catName = $achievement->category->name ?? $achievement->achievement_type ?? '—';
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold text-dark">{{ $achievement->alumni->full_name ?? '—' }}</div>
                  @if ($achievement->alumni?->batch)
                    <div class="text-muted" style="font-size: 0.75rem;">
                      {{ $achievement->alumni->batch->ict_name ?: $achievement->alumni->batch->batch_name }}
                    </div>
                  @endif
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $achievement->title }}</div>
                  <div class="text-muted" style="font-size: 0.8rem;">{{ \Illuminate\Support\Str::limit($achievement->description, 70) }}</div>
                  @if ($achievement->image)
                    <a href="{{ asset('storage/' . $achievement->image) }}" target="_blank" class="small text-primary text-decoration-none">View Attached Image &rarr;</a>
                  @endif
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary">{{ $catName }}</span>
                </td>
                <td class="text-muted">{{ $achievement->organization ?: '—' }}</td>
                <td>
                  <span class="badge {{ $achievement->status === 'approved' ? 'bg-success' : ($achievement->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                    {{ ucfirst($achievement->status) }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    @if ($achievement->status === 'pending')
                      <form method="POST" action="{{ route('admin.achievements.approve', $achievement) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-brass py-0 px-2">Approve</button>
                      </form>
                      <form method="POST" action="{{ route('admin.achievements.reject', $achievement) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Reject</button>
                      </form>
                    @endif
                    <form method="POST" action="{{ route('admin.achievements.destroy', $achievement) }}" onsubmit="return confirm('Delete this achievement record?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2 text-danger">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-4">{{ $achievements->links() }}</div>
    @endif
  </div>
@endsection
