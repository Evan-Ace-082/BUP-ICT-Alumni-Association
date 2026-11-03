@extends('layouts.dashboard')

@section('title', 'Alumni Verifications — BUP ICT Alumni Association')
@section('page_title', 'Alumni Account Verifications')

@section('content')
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card-feature p-3 h-100">
        <div class="small text-muted-slate mb-1">Pending Approval</div>
        <div class="h3 mb-0 text-warning">{{ $counts['pending'] }}</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature p-3 h-100">
        <div class="small text-muted-slate mb-1">Verified Alumni</div>
        <div class="h3 mb-0 text-success">{{ $counts['verified'] }}</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature p-3 h-100">
        <div class="small text-muted-slate mb-1">Rejected Verifications</div>
        <div class="h3 mb-0 text-danger">{{ $counts['rejected'] }}</div>
      </div>
    </div>
  </div>

  <div class="card-feature h-auto mb-4">
    <form method="GET" action="{{ route('admin.alumni-verifications.index') }}" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small" for="search">Search</label>
        <input
          type="text"
          name="search"
          id="search"
          class="form-control"
          placeholder="Student ID, name, email, or Alumni ID"
          value="{{ $filters['search'] ?? '' }}"
        >
      </div>
      <div class="col-md-4">
        <label class="form-label small" for="status">Verification Status</label>
        <select name="status" id="status" class="form-select">
          <option value="">All statuses</option>
          <option value="pending" @selected(($filters['status'] ?? 'pending') === 'pending')>Pending Review ({{ $counts['pending'] }})</option>
          <option value="verified" @selected(($filters['status'] ?? null) === 'verified')>Verified ({{ $counts['verified'] }})</option>
          <option value="rejected" @selected(($filters['status'] ?? null) === 'rejected')>Rejected ({{ $counts['rejected'] }})</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass w-100">Filter</button>
        @if (array_filter($filters ?? []))
          <a href="{{ route('admin.alumni-verifications.index') }}" class="btn btn-outline-ink">Clear</a>
        @endif
      </div>
    </form>
  </div>

  <div class="card-feature h-auto">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">
        Registered Alumni Accounts
        <span class="text-muted-slate small fw-normal">— {{ $requests->total() }} total</span>
      </h2>
    </div>

    @if ($requests->isEmpty())
      <div class="placeholder-panel">
        @if (array_filter($filters ?? []))
          <h3 class="h6 mb-2">No matching verification requests</h3>
          <p class="mb-0">Try a different search term or clear the filters above.</p>
        @else
          <h3 class="h6 mb-2">No verification requests found</h3>
          <p class="mb-0">There are currently no alumni accounts under this filter.</p>
        @endif
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle mb-0">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Batch</th>
              <th>Session</th>
              <th>Grad Year</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $item)
              <tr>
                <td><strong>{{ $item->student_id }}</strong></td>
                <td>
                  <div>{{ $item->full_name }}</div>
                  @if ($item->alumni_id)
                    <div class="badge bg-light text-dark font-monospace">{{ $item->alumni_id }}</div>
                  @endif
                </td>
                <td>{{ $item->user?->email ?? $item->email }}</td>
                <td>{{ $item->batch?->batch_name ?? '—' }}</td>
                <td>{{ $item->session ?? ($item->batch?->session ?? '—') }}</td>
                <td>{{ $item->graduation_year ?? ($item->batch?->passing_year ?? '—') }}</td>
                <td>
                  @if ($item->verification_status === 'verified')
                    <span class="badge bg-success">Verified</span>
                  @elseif ($item->verification_status === 'rejected')
                    <span class="badge bg-danger">Rejected</span>
                  @else
                    <span class="badge bg-warning text-dark">Pending</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <a href="{{ route('admin.alumni-verifications.show', $item) }}" class="btn btn-sm btn-outline-ink">
                      View Details
                    </a>

                    @if ($item->verification_status !== 'verified')
                      <form method="POST" action="{{ route('admin.alumni-verifications.approve', $item) }}" onsubmit="return confirm('Approve verification for {{ addslashes($item->full_name) }}? This will issue an official Digital Alumni ID.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-brass">
                          Approve
                        </button>
                      </form>
                    @endif

                    @if ($item->verification_status !== 'rejected')
                      <form method="POST" action="{{ route('admin.alumni-verifications.reject', $item) }}" onsubmit="return confirm('Reject verification for {{ addslashes($item->full_name) }}?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          Reject
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if ($requests->hasPages())
        <div class="mt-3">
          {{ $requests->links() }}
        </div>
      @endif
    @endif
  </div>
@endsection
