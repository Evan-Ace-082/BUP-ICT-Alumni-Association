@extends('layouts.dashboard')
@section('title', 'Approved Mentors — Admin Panel')
@section('page_title', 'Mentor Management')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">Approved Mentors Directory</h1>
      <p class="small text-muted-slate mb-0">Manage active and suspended mentors, monitor mentee capacity, and availability states.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
  </div>

  {{-- Filters --}}
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('admin.mentorship.mentors') }}" class="row g-2">
      <div class="col-md-5">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, company, or designation..." value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses (Approved &amp; Suspended)</option>
          <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved Only</option>
          <option value="suspended" {{ ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
        </select>
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-brass btn-sm">Filter</button>
        <a href="{{ route('admin.mentorship.mentors') }}" class="btn btn-ghost-sm btn-sm">Reset</a>
      </div>
    </form>
  </div>

  @if ($mentors->isEmpty())
    <div class="card-feature text-center py-5">
      <p class="small text-muted-slate mb-0">No mentors found matching your criteria.</p>
    </div>
  @else
    <div class="card-feature p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted-slate">
              <th>Mentor</th>
              <th>Cohort</th>
              <th>Professional Role</th>
              <th>Categories</th>
              <th>Active / Capacity</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            @foreach ($mentors as $mentor)
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    @if ($mentor->alumni?->profile_photo)
                      <img src="{{ asset('storage/' . $mentor->alumni->profile_photo) }}" alt="" class="rounded-circle" style="width: 34px; height: 34px; object-fit: cover;">
                    @else
                      <div class="rounded-circle bg-brass-subtle text-brass d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 13px;">
                        {{ substr($mentor->alumni?->full_name ?? $mentor->user->name, 0, 1) }}
                      </div>
                    @endif
                    <div>
                      <div class="fw-semibold text-ink-900">{{ $mentor->alumni?->full_name ?? $mentor->user->name }}</div>
                      <div class="text-muted-slate" style="font-size: 11px;">{{ $mentor->user->email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-brass-subtle text-brass">{{ $mentor->alumni?->batch?->batch_code }}</span>
                  <div class="text-muted-slate" style="font-size: 11px;">Graduated {{ $mentor->alumni?->graduation_year ?? 'N/A' }}</div>
                </td>
                <td>
                  <div class="fw-semibold text-ink-900">{{ $mentor->current_designation }}</div>
                  <div class="text-muted-slate">{{ $mentor->company }} &middot; {{ $mentor->city }}</div>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1" style="max-width: 180px;">
                    @foreach ($mentor->categories->take(2) as $cat)
                      <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">{{ $cat->name }}</span>
                    @endforeach
                    @if ($mentor->categories->count() > 2)
                      <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">+{{ $mentor->categories->count() - 2 }}</span>
                    @endif
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">{{ $mentor->active_requests_count }} / {{ $mentor->max_mentees }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    @if ($mentor->is_paused)
                      <span class="text-warning">Paused</span>
                    @elseif ($mentor->remaining_slots > 0)
                      <span class="text-success">{{ $mentor->remaining_slots }} slots open</span>
                    @else
                      <span class="text-secondary">Fully booked</span>
                    @endif
                  </div>
                </td>
                <td>
                  @if ($mentor->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @elseif ($mentor->status === 'suspended')
                    <span class="badge bg-danger">Suspended</span>
                  @else
                    <span class="badge bg-secondary">{{ ucfirst($mentor->status) }}</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('admin.mentorship.applications.show', $mentor) }}" class="btn btn-outline-ink btn-sm py-1 px-2" style="font-size: 11px;">
                      View
                    </a>
                    @if ($mentor->status === 'approved')
                      <form method="POST" action="{{ route('admin.mentorship.mentors.suspend', $mentor) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" style="font-size: 11px;" onclick="return confirm('Suspend this mentor? Their profile will be hidden from the directory.')">
                          Suspend
                        </button>
                      </form>
                    @elseif ($mentor->status === 'suspended')
                      <form method="POST" action="{{ route('admin.mentorship.mentors.reactivate', $mentor) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2" style="font-size: 11px;" onclick="return confirm('Reactivate this mentor?')">
                          Reactivate
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
      <div class="p-3 border-top">
        {{ $mentors->links() }}
      </div>
    </div>
  @endif
@endsection
