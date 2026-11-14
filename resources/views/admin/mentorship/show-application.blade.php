@extends('layouts.dashboard')
@section('title', 'Review Application — ' . ($profile->alumni?->full_name ?? $profile->user->name))
@section('page_title', 'Review Mentor Application')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.mentorship.applications') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Applications</a>
    <div class="d-flex align-items-center gap-2">
      <span class="small text-muted-slate">Current Status:</span>
      @if ($profile->status === 'approved')
        <span class="badge bg-success">Approved</span>
      @elseif ($profile->status === 'pending')
        <span class="badge bg-warning text-dark">Pending Review</span>
      @elseif ($profile->status === 'suspended')
        <span class="badge bg-danger">Suspended</span>
      @else
        <span class="badge bg-secondary">Rejected</span>
      @endif
    </div>
  </div>

  <div class="row g-4 mb-4">
    {{-- Left column: Applicant identity & alumni verification status --}}
    <div class="col-lg-4">
      <div class="card-feature mb-4">
        <div class="text-center mb-3">
          @if ($profile->alumni?->profile_photo)
            <img src="{{ asset('storage/' . $profile->alumni->profile_photo) }}" alt="" class="rounded-circle img-thumbnail mb-2" style="width: 90px; height: 90px; object-fit: cover;">
          @else
            <div class="rounded-circle bg-brass-subtle text-brass d-inline-flex align-items-center justify-content-center fw-bold mb-2" style="width: 90px; height: 90px; font-size: 32px;">
              {{ substr($profile->alumni?->full_name ?? $profile->user->name, 0, 1) }}
            </div>
          @endif
          <h2 class="h6 mb-0">{{ $profile->alumni?->full_name ?? $profile->user->name }}</h2>
          <div class="small text-muted-slate">{{ $profile->user->email }}</div>
        </div>

        <div class="border-top pt-2 small">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Student ID:</span>
            <span class="fw-semibold">{{ $profile->alumni?->student_id }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Batch:</span>
            <span class="fw-semibold">{{ $profile->alumni?->batch?->batch_code }} &mdash; {{ $profile->alumni?->batch?->batch_name }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Graduation Year:</span>
            <span class="fw-semibold">{{ $profile->alumni?->graduation_year ?? 'N/A' }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Alumni Verification:</span>
            @if ($profile->alumni?->isVerified())
              <span class="badge bg-success-subtle text-success">Verified Alumni</span>
            @else
              <span class="badge bg-warning-subtle text-warning">Unverified</span>
            @endif
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Applied On:</span>
            <span>{{ $profile->created_at->format('M d, Y h:i A') }}</span>
          </div>
        </div>
      </div>

      {{-- Admin Action Card --}}
      <div class="card-feature border">
        <h3 class="h6 mb-3">Administrative Decision</h3>

        {{-- Approve Form --}}
        <form method="POST" action="{{ route('admin.mentorship.applications.approve', $profile) }}" class="mb-3">
          @csrf
          @method('PATCH')
          <div class="mb-2">
            <label class="form-label small fw-semibold" for="approve_notes">Internal Admin Notes (Optional)</label>
            <textarea name="admin_notes" id="approve_notes" rows="2" class="form-control form-control-sm" placeholder="Private internal note...">{{ $profile->admin_notes }}</textarea>
          </div>
          <button type="submit" class="btn btn-brass w-100 btn-sm">
            Approve Mentor Application
          </button>
        </form>

        <hr class="my-3">

        {{-- Reject Form --}}
        <form method="POST" action="{{ route('admin.mentorship.applications.reject', $profile) }}">
          @csrf
          @method('PATCH')
          <div class="mb-2">
            <label class="form-label small fw-semibold text-danger" for="rejection_reason">Reason for Rejection <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" id="rejection_reason" rows="2" class="form-control form-control-sm" placeholder="Will be sent to the applicant via email..." required>{{ $profile->rejection_reason }}</textarea>
          </div>
          <button type="submit" class="btn btn-outline-danger w-100 btn-sm" onclick="return confirm('Reject this mentor application?')">
            Reject Application
          </button>
        </form>
      </div>
    </div>

    {{-- Right column: Submitted professional details, categories, personal statement --}}
    <div class="col-lg-8">
      <div class="card-feature mb-4">
        <h3 class="h6 mb-3">Professional Background</h3>
        <div class="row g-2 small mb-3">
          <div class="col-md-6">
            <span class="text-muted-slate">Current Designation:</span>
            <div class="fw-semibold text-ink-900">{{ $profile->current_designation }}</div>
          </div>
          <div class="col-md-6">
            <span class="text-muted-slate">Company:</span>
            <div class="fw-semibold text-ink-900">{{ $profile->company }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Industry:</span>
            <div>{{ $profile->industry ?: '—' }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Location:</span>
            <div>{{ $profile->city }}, {{ $profile->country }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Experience:</span>
            <div>{{ $profile->years_of_experience }} Years</div>
          </div>
          @if ($profile->linkedin_url)
            <div class="col-12 mt-2">
              <span class="text-muted-slate">LinkedIn:</span>
              <a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener noreferrer">{{ $profile->linkedin_url }}</a>
            </div>
          @endif
        </div>

        <h4 class="small fw-semibold text-ink-900 mb-1">Biography</h4>
        <p class="small text-muted-slate mb-4" style="line-height: 1.6; white-space: pre-line;">{{ $profile->bio }}</p>

        <h3 class="h6 mb-2">Selected Mentorship Categories</h3>
        <div class="d-flex flex-wrap gap-2 mb-4">
          @foreach ($profile->categories as $category)
            <span class="badge bg-brass-subtle text-brass p-2" style="font-size: 12px;">
              &check; {{ $category->name }}
            </span>
          @endforeach
        </div>

        <h3 class="h6 mb-2">Mentorship Preferences</h3>
        <div class="row g-2 small mb-4">
          <div class="col-md-4">
            <span class="text-muted-slate">Audience:</span>
            <div class="fw-semibold">{{ ucfirst($profile->preferred_mentee_audience) }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Mode:</span>
            <div class="fw-semibold">{{ ucfirst($profile->mentorship_mode) }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Language:</span>
            <div class="fw-semibold">{{ $profile->preferred_language }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Max Mentees:</span>
            <div class="fw-semibold">{{ $profile->max_mentees }} active mentees</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Frequency:</span>
            <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $profile->mentorship_frequency)) }}</div>
          </div>
          <div class="col-md-4">
            <span class="text-muted-slate">Schedule:</span>
            <div>{{ $profile->availability_schedule ?: 'Flexible' }}</div>
          </div>
        </div>

        @if ($profile->topics_offered)
          <h4 class="small fw-semibold text-ink-900 mb-1">Guidance Topics Offered</h4>
          <p class="small text-muted-slate mb-4">{{ $profile->topics_offered }}</p>
        @endif

        <h3 class="h6 mb-2">Personal Statement</h3>
        <div class="p-3 rounded bg-paper-subtle border small">
          <em>"{{ $profile->personal_statement }}"</em>
        </div>
      </div>
    </div>
  </div>
@endsection
