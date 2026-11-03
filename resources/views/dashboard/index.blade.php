@extends('layouts.dashboard')

@section('title', 'Dashboard — BUP ICT Alumni Association')
@section('page_title', 'My Dashboard')

@section('content')
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('status'))
    <div class="alert alert-info alert-dismissible fade show small mb-3" role="alert">
      {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Verification Status Alerts --}}
  @if ($alumni)
    @if ($alumni->isPendingVerification())
      <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 p-3 d-flex align-items-center gap-3" role="alert">
        <div class="fs-3 text-warning">⏳</div>
        <div>
          <div class="fw-bold mb-1">
            Your email has been verified successfully. Your alumni verification is pending admin approval.
          </div>
          <div class="small text-muted-slate">
            You can access your account, explore announcements, and update your profile. Verified features such as your official Digital Alumni ID, voting, and achievement submissions will be activated upon administrator review.
          </div>
        </div>
      </div>
    @elseif ($alumni->isVerified())
      <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
        <div class="d-flex align-items-center gap-3">
          <div class="fs-3 text-success">✓</div>
          <div>
            <div class="fw-bold mb-1">Your alumni account has been verified!</div>
            <div class="small text-muted-slate">
              Your official Digital Alumni ID <span class="font-monospace fw-bold">{{ $alumni->alumni_id }}</span> and QR verification code are active.
            </div>
          </div>
        </div>
        <div>
          <a href="{{ route('dashboard.digital-id') }}" class="btn btn-sm btn-brass">
            View My Digital ID &rarr;
          </a>
        </div>
      </div>
    @elseif ($alumni->isVerificationRejected())
      <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3 d-flex align-items-center gap-3" role="alert">
        <div class="fs-3 text-danger">✗</div>
        <div>
          <div class="fw-bold mb-1">Alumni verification request was not approved</div>
          <div class="small text-muted-slate">
            {{ $alumni->rejection_reason ?: 'Your submitted information could not be verified by an administrator. Please update your profile or contact the department.' }}
          </div>
        </div>
      </div>
    @endif
  @endif

  <div class="card-feature h-auto mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h2 class="h5 mb-1">Welcome, {{ auth()->user()?->name ?? 'Alumnus' }}</h2>
        <p class="small text-muted-slate mb-0">Department of Information and Communication Technology, BUP</p>
      </div>
      <div>
        @if ($alumni)
          @if ($alumni->isVerified())
            <span class="badge bg-success">Verified Alumnus</span>
          @elseif ($alumni->isVerificationRejected())
            <span class="badge bg-danger">Verification Rejected</span>
          @else
            <span class="badge bg-warning text-dark">Verification Pending</span>
          @endif
        @endif
      </div>
    </div>

    @if ($alumni)
      <div class="row g-3 pt-2">
        <div class="col-sm-6 col-md-3">
          <div class="small text-muted-slate">Student ID</div>
          <div class="fw-bold">{{ $alumni->student_id }}</div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="small text-muted-slate">Batch</div>
          <div class="fw-bold">{{ $alumni->batch?->batch_name ?? '—' }}</div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="small text-muted-slate">Session</div>
          <div>{{ $alumni->session ?: ($alumni->batch?->session ?: '—') }}</div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="small text-muted-slate">Digital Alumni ID</div>
          <div class="font-monospace fw-bold text-brass">{{ $alumni->alumni_id ?: 'Pending Approval' }}</div>
        </div>
      </div>
    @endif
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card-feature h-100 p-3">
        <h3 class="h6 mb-2">My Digital ID Card</h3>
        <p class="small text-muted-slate mb-3">
          Access your digital alumni identification card, official QR code, and public verification status.
        </p>
        <a href="{{ route('dashboard.digital-id') }}" class="btn btn-sm btn-brass w-100">
          Open Digital ID
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature h-100 p-3">
        <h3 class="h6 mb-2">My Profile</h3>
        <p class="small text-muted-slate mb-3">
          Update your employment details, social handles, current country, and mentorship availability.
        </p>
        <a href="{{ route('dashboard.profile.edit') }}" class="btn btn-sm btn-outline-ink w-100">
          Edit Profile
        </a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature h-100 p-3">
        <h3 class="h6 mb-2">My Batch</h3>
        <p class="small text-muted-slate mb-3">
          Connect with graduates from your batch, search batchmates, and view batch statistics.
        </p>
        <a href="{{ route('dashboard.batch') }}" class="btn btn-sm btn-outline-ink w-100">
          View Batchmates
        </a>
      </div>
    </div>
  </div>
@endsection
