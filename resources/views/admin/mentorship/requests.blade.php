@extends('layouts.dashboard')
@section('title', 'All Mentorship Requests — Admin Panel')
@section('page_title', 'Mentorship Requests & Engagements')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">System-Wide Mentorship Requests</h1>
      <p class="small text-muted-slate mb-0">Monitor request flows, engagement statuses, and student-mentor pairings.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
  </div>

  {{-- Filters --}}
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('admin.mentorship.requests') }}" class="row g-2">
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="accepted" {{ $currentStatus === 'accepted' ? 'selected' : '' }}>Accepted / Active</option>
          <option value="declined" {{ $currentStatus === 'declined' ? 'selected' : '' }}>Declined</option>
          <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
      </div>
      <div class="col-md-4">
        <select name="category_id" class="form-select form-select-sm">
          <option value="">All Mentorship Categories</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" {{ $selectedCategory == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-5 d-flex gap-2">
        <button type="submit" class="btn btn-brass btn-sm">Filter</button>
        <a href="{{ route('admin.mentorship.requests') }}" class="btn btn-ghost-sm btn-sm">Reset</a>
      </div>
    </form>
  </div>

  @if ($requests->isEmpty())
    <div class="card-feature text-center py-5">
      <p class="small text-muted-slate mb-0">No mentorship requests found matching your filter criteria.</p>
    </div>
  @else
    <div class="card-feature p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted-slate">
              <th>Mentee</th>
              <th>Mentor</th>
              <th>Category &amp; Topic</th>
              <th>Mode</th>
              <th>Requested</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            @foreach ($requests as $req)
              @php
                $menteeName = $req->mentee->alumni?->full_name ?? $req->mentee->student?->full_name ?? $req->mentee->name;
                $mentorName = $req->mentor->alumni?->full_name ?? $req->mentor->name;
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold text-ink-900">{{ $menteeName }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    {{ $req->mentee->student ? 'Student' : ($req->mentee->alumni?->batch?->batch_code ?? 'Alumni') }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-ink-900">{{ $mentorName }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    {{ $req->mentor->alumni?->batch?->batch_code ?? 'Mentor' }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">{{ $req->category?->name ?? 'General' }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">{{ Str::limit($req->specific_topic, 45) }}</div>
                </td>
                <td>{{ ucfirst($req->preferred_mode) }}</td>
                <td>{{ $req->created_at->format('M d, Y') }}</td>
                <td>
                  @if ($req->status === 'accepted')
                    <span class="badge bg-success">Active</span>
                  @elseif ($req->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @elseif ($req->status === 'completed')
                    <span class="badge bg-primary">Completed</span>
                  @elseif ($req->status === 'declined' || $req->status === 'rejected')
                    <span class="badge bg-secondary">Declined</span>
                  @else
                    <span class="badge bg-light border text-muted-slate">{{ ucfirst($req->status) }}</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="p-3 border-top">
        {{ $requests->links() }}
      </div>
    </div>
  @endif
@endsection
