@extends('layouts.dashboard')

@section('title', 'Verification Requests — BUP ICT Alumni Association')
@section('page_title', 'Verification Requests')


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

  <div class="card-feature h-auto mb-4">
    <form method="GET" action="{{ route('admin.verifications.index') }}" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small" for="search">Search</label>
        <input
          type="text"
          name="search"
          id="search"
          class="form-control"
          placeholder="Student ID, name, or email"
          value="{{ $filters['search'] ?? '' }}"
        >
      </div>
      <div class="col-md-4">
        <label class="form-label small" for="status">Status</label>
        <select name="status" id="status" class="form-select">
          <option value="">All statuses</option>
          <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending ({{ $pendingCount }})</option>
          <option value="approved" @selected(($filters['status'] ?? null) === 'approved')>Approved</option>
          <option value="rejected" @selected(($filters['status'] ?? null) === 'rejected')>Rejected</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass w-100">Filter</button>
        @if (array_filter($filters ?? []))
          <a href="{{ route('admin.verifications.index') }}" class="btn btn-outline-ink">Clear</a>
        @endif
      </div>
    </form>
  </div>

  <div class="card-feature h-auto">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">
        Verification Requests
        <span class="text-muted-slate small fw-normal">— {{ $requests->total() }} total</span>
      </h2>
    </div>

    @if ($requests->isEmpty())
      <div class="placeholder-panel">
        @if (array_filter($filters ?? []))
          <h3 class="h6 mb-2">No matching verification requests</h3>
          <p class="mb-0">Try a different search term or clear the filters above.</p>
        @else
          <h3 class="h6 mb-2">No verification requests yet</h3>
          <p class="mb-0">There are currently no verification requests awaiting review.</p>
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
              <th>Phone</th>
              <th>Submitted</th>
              <th>Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $req)
              <tr>
                <td><strong>{{ $req->student_id ?? '—' }}</strong></td>
                <td>{{ $req->full_name }}</td>
                <td>{{ $req->email }}</td>
                <td>{{ $req->phone ?? '—' }}</td>
                <td>{{ $req->created_at->format('M d, Y') }}</td>
                <td>
                  @if ($req->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @elseif ($req->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @else
                    <span class="badge bg-danger">Rejected</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('admin.verifications.show', $req) }}" class="btn btn-sm btn-brass">
                    Review
                  </a>
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
