@extends('layouts.dashboard')
@section('title', 'Mentorship Management — Admin Panel')
@section('page_title', 'Mentorship System Management')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  {{-- Top Navigation Pills --}}
  <div class="d-flex flex-wrap gap-2 mb-4 border-bottom pb-3">
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-brass btn-sm">Overview</a>
    <a href="{{ route('admin.mentorship.applications') }}" class="btn btn-outline-ink btn-sm">
      Mentor Applications
      @if ($stats['pending_applications'] > 0)
        <span class="badge bg-danger ms-1">{{ $stats['pending_applications'] }}</span>
      @endif
    </a>
    <a href="{{ route('admin.mentorship.mentors') }}" class="btn btn-outline-ink btn-sm">Approved Mentors ({{ $stats['approved_mentors'] }})</a>
    <a href="{{ route('admin.mentorship.requests') }}" class="btn btn-outline-ink btn-sm">All Requests ({{ $stats['total_requests'] }})</a>
    <a href="{{ route('admin.mentorship.categories') }}" class="btn btn-outline-ink btn-sm">Categories ({{ $stats['categories_count'] }})</a>
    <a href="{{ route('admin.mentorship.settings') }}" class="btn btn-outline-ink btn-sm">Cohort &amp; Settings</a>
    <a href="{{ route('admin.mentorship.overrides') }}" class="btn btn-outline-ink btn-sm">User Overrides ({{ $stats['overrides_count'] }})</a>
  </div>

  {{-- Metrics Cards (Section 13) --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Approved Mentors</div>
        <div class="h3 mb-0 text-success">{{ $stats['approved_mentors'] }}</div>
        <div class="small text-muted-slate mt-1">{{ $stats['suspended_mentors'] }} suspended</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Pending Applications</div>
        <div class="h3 mb-0 {{ $stats['pending_applications'] > 0 ? 'text-danger' : 'text-ink-900' }}">
          {{ $stats['pending_applications'] }}
        </div>
        <div class="small text-muted-slate mt-1">Awaiting Review</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Active Mentorships</div>
        <div class="h3 mb-0 text-brass">{{ $stats['active_mentorships'] }}</div>
        <div class="small text-muted-slate mt-1">Currently in progress</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Completed Engagements</div>
        <div class="h3 mb-0 text-ink-900">{{ $stats['completed_mentorships'] }}</div>
        <div class="small text-muted-slate mt-1">{{ $stats['total_requests'] }} total requests</div>
      </div>
    </div>
  </div>

  {{-- Cohort Detection Status Card (Section 12) --}}
  <div class="card-feature mb-4 border-brass">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <div>
        <h2 class="h6 mb-0 text-ink-900">Cohort Detection Configuration</h2>
        <div class="small text-muted-slate">Controls automatic vs. manual cohort determination for mentee/mentor eligibility.</div>
      </div>
      <a href="{{ route('admin.mentorship.settings') }}" class="btn btn-outline-ink btn-sm">Configure Settings &rarr;</a>
    </div>
    <div class="row g-2 small mt-1">
      <div class="col-md-3">
        <span class="text-muted-slate">Current Mode:</span>
        <strong class="badge {{ $auditData['mode'] === 'automatic' ? 'bg-success' : 'bg-primary' }} text-white ms-1">{{ ucfirst($auditData['mode']) }}</strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted-slate">Latest Completed Cohort:</span>
        <strong class="text-ink-900 ms-1">Class of {{ $auditData['effective_latest_year'] ?? 'Not set' }}</strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted-slate">Batches with Year Data:</span>
        <strong class="ms-1">{{ $auditData['batches_with_year'] }} / {{ $auditData['total_batches'] }}</strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted-slate">Auto-Detect Readiness:</span>
        @if ($auditData['is_auto_ready'])
          <span class="badge bg-success-subtle text-success ms-1">Ready</span>
        @else
          <span class="badge bg-warning-subtle text-warning ms-1">Manual Mode Recommended</span>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- Recent Applications --}}
    <div class="col-lg-6">
      <div class="card-feature h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 mb-0">Recent Mentor Applications</h2>
          <a href="{{ route('admin.mentorship.applications') }}" class="btn btn-ghost-sm btn-sm">View All &rarr;</a>
        </div>
        @if ($recentApplications->isEmpty())
          <p class="small text-muted-slate mb-0">No mentor applications received yet.</p>
        @else
          <div class="d-flex flex-column gap-2">
            @foreach ($recentApplications as $app)
              <div class="p-2 border rounded small d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold text-ink-900">{{ $app->alumni?->full_name ?? $app->user->name }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    {{ $app->current_designation }} at {{ $app->company }} &middot; {{ $app->alumni?->batch?->batch_code }}
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  @if ($app->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @elseif ($app->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @elseif ($app->status === 'suspended')
                    <span class="badge bg-danger">Suspended</span>
                  @else
                    <span class="badge bg-secondary">Rejected</span>
                  @endif
                  <a href="{{ route('admin.mentorship.applications.show', $app) }}" class="btn btn-outline-ink btn-sm py-0 px-2" style="font-size: 11px;">Review</a>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    {{-- Recent Mentorship Requests --}}
    <div class="col-lg-6">
      <div class="card-feature h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 mb-0">Recent Mentorship Requests</h2>
          <a href="{{ route('admin.mentorship.requests') }}" class="btn btn-ghost-sm btn-sm">View All &rarr;</a>
        </div>
        @if ($recentRequests->isEmpty())
          <p class="small text-muted-slate mb-0">No mentorship requests sent yet.</p>
        @else
          <div class="d-flex flex-column gap-2">
            @foreach ($recentRequests as $rReq)
              <div class="p-2 border rounded small d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold text-ink-900">
                    {{ $rReq->mentee->alumni?->full_name ?? $rReq->mentee->student?->full_name ?? $rReq->mentee->name }} &rarr; {{ $rReq->mentor->alumni?->full_name ?? $rReq->mentor->name }}
                  </div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    {{ $rReq->category?->name ?? 'General' }} &middot; {{ $rReq->created_at->diffForHumans() }}
                  </div>
                </div>
                <span class="badge bg-light border text-muted-slate">{{ ucfirst($rReq->status) }}</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection
