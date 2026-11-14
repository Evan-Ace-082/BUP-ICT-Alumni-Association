@extends('layouts.dashboard')
@section('title', 'Mentor Applications — Admin Panel')
@section('page_title', 'Mentor Applications Review')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">Senior Alumni Mentor Applications</h1>
      <p class="small text-muted-slate mb-0">Review credentials, professional background, and expertise areas before approval.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
  </div>

  {{-- Status Tabs --}}
  <div class="d-flex flex-wrap gap-1 mb-4">
    <a href="{{ route('admin.mentorship.applications', ['status' => 'pending']) }}" class="btn btn-sm {{ $currentStatus === 'pending' ? 'btn-brass' : 'btn-outline-ink' }}">
      Pending Review
    </a>
    <a href="{{ route('admin.mentorship.applications', ['status' => 'approved']) }}" class="btn btn-sm {{ $currentStatus === 'approved' ? 'btn-brass' : 'btn-outline-ink' }}">
      Approved
    </a>
    <a href="{{ route('admin.mentorship.applications', ['status' => 'rejected']) }}" class="btn btn-sm {{ $currentStatus === 'rejected' ? 'btn-brass' : 'btn-outline-ink' }}">
      Rejected
    </a>
    <a href="{{ route('admin.mentorship.applications', ['status' => 'suspended']) }}" class="btn btn-sm {{ $currentStatus === 'suspended' ? 'btn-brass' : 'btn-outline-ink' }}">
      Suspended
    </a>
    <a href="{{ route('admin.mentorship.applications', ['status' => 'all']) }}" class="btn btn-sm {{ $currentStatus === 'all' ? 'btn-brass' : 'btn-outline-ink' }}">
      All Applications
    </a>
  </div>

  @if ($applications->isEmpty())
    <div class="card-feature text-center py-5">
      <p class="small text-muted-slate mb-0">No mentor applications found under the '{{ ucfirst($currentStatus) }}' filter.</p>
    </div>
  @else
    <div class="card-feature p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted-slate">
              <th>Applicant</th>
              <th>Cohort</th>
              <th>Designation &amp; Company</th>
              <th>Categories</th>
              <th>Submitted</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            @foreach ($applications as $app)
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    @if ($app->alumni?->profile_photo)
                      <img src="{{ asset('storage/' . $app->alumni->profile_photo) }}" alt="" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                    @else
                      <div class="rounded-circle bg-brass-subtle text-brass d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                        {{ substr($app->alumni?->full_name ?? $app->user->name, 0, 1) }}
                      </div>
                    @endif
                    <div>
                      <div class="fw-semibold text-ink-900">{{ $app->alumni?->full_name ?? $app->user->name }}</div>
                      <div class="text-muted-slate" style="font-size: 11px;">{{ $app->user->email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-brass-subtle text-brass">{{ $app->alumni?->batch?->batch_code }}</span>
                  <div class="text-muted-slate" style="font-size: 11px;">Class of {{ $app->alumni?->graduation_year ?? 'N/A' }}</div>
                </td>
                <td>
                  <div class="fw-semibold text-ink-900">{{ $app->current_designation }}</div>
                  <div class="text-muted-slate">{{ $app->company }} &middot; {{ $app->city }}</div>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1" style="max-width: 200px;">
                    @foreach ($app->categories->take(2) as $cat)
                      <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">{{ $cat->name }}</span>
                    @endforeach
                    @if ($app->categories->count() > 2)
                      <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">+{{ $app->categories->count() - 2 }}</span>
                    @endif
                  </div>
                </td>
                <td>{{ $app->created_at->format('M d, Y') }}</td>
                <td>
                  @if ($app->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @elseif ($app->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @elseif ($app->status === 'suspended')
                    <span class="badge bg-danger">Suspended</span>
                  @else
                    <span class="badge bg-secondary">Rejected</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('admin.mentorship.applications.show', $app) }}" class="btn btn-outline-ink btn-sm py-1 px-2" style="font-size: 11px;">
                      Review Details
                    </a>
                    @if ($app->status === 'pending')
                      <form method="POST" action="{{ route('admin.mentorship.applications.approve', $app) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-brass btn-sm py-1 px-2" style="font-size: 11px;" onclick="return confirm('Approve this mentor application?')">
                          Approve
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
        {{ $applications->links() }}
      </div>
    </div>
  @endif
@endsection
