@extends('layouts.dashboard')

@section('title', 'Alumni Details — '.$alumni->full_name)
@section('page_title', 'Alumni Verification Review')

@section('content')
  <a href="{{ route('admin.alumni-verifications.index') }}" class="small d-inline-block mb-3">&larr; Back to Alumni Verifications</a>

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
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h2 class="h5 mb-1">{{ $alumni->full_name }}</h2>
        <p class="small text-muted-slate mb-0">Registered account linked on {{ $alumni->updated_at->format('F d, Y \a\t h:i A') }}</p>
      </div>
      <div>
        @if ($alumni->verification_status === 'verified')
          <span class="badge bg-success fs-6">✓ Verified Alumni</span>
        @elseif ($alumni->verification_status === 'rejected')
          <span class="badge bg-danger fs-6">✗ Verification Rejected</span>
        @else
          <span class="badge bg-warning text-dark fs-6">⏳ Verification Pending</span>
        @endif
      </div>
    </div>

    @if ($alumni->alumni_id)
      <div class="alert alert-success small mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <strong>Issued Alumni ID:</strong> <span class="font-monospace fw-bold">{{ $alumni->alumni_id }}</span>
          <span class="ms-2 text-muted-slate">Verified by {{ $alumni->verifier?->name ?? 'Administrator' }} on {{ $alumni->verified_at?->format('M d, Y h:i A') }}</span>
        </div>
        @if ($alumni->qr_token)
          <div>
            <a href="{{ route('alumni.verify.qr', ['token' => $alumni->qr_token]) }}" target="_blank" class="btn btn-sm btn-outline-success">
              View Public QR Verification Page &rarr;
            </a>
          </div>
        @endif
      </div>
    @endif

    @if ($alumni->verification_status === 'rejected' && $alumni->rejection_reason)
      <div class="alert alert-danger small mb-3">
        <strong>Rejection Reason:</strong> {{ $alumni->rejection_reason }}
        <div class="text-muted small mt-1">Rejected by {{ $alumni->rejecter?->name ?? 'Administrator' }} on {{ $alumni->rejected_at?->format('M d, Y h:i A') }}</div>
      </div>
    @endif

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="small text-muted-slate">Student ID</div>
        <div><strong>{{ $alumni->student_id }}</strong></div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Account Email</div>
        <div>{{ $alumni->user?->email ?? $alumni->email }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Phone Number</div>
        <div>{{ $alumni->phone ?: ($alumni->user?->phone ?: '—') }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Batch</div>
        <div>{{ $alumni->batch?->batch_name ?? '—' }} ({{ $alumni->batch?->batch_code ?? '—' }})</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Session</div>
        <div>{{ $alumni->session ?: ($alumni->batch?->session ?: '—') }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Graduation Year</div>
        <div>{{ $alumni->graduation_year ?: ($alumni->batch?->passing_year ?: '—') }}</div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="small text-muted-slate">Current Company / Organization</div>
        <div>{{ $alumni->company ?: '—' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Designation</div>
        <div>{{ $alumni->designation ?: '—' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted-slate">Current Institution</div>
        <div>{{ $alumni->current_institution ?: '—' }}</div>
      </div>
    </div>

    @if ($alumni->isVerified() && $alumni->qr_token)
      <div class="row g-3 mb-4 p-3 bg-light rounded">
        <div class="col-md-3 text-center">
          <div class="p-2 bg-white rounded shadow-sm d-inline-block">
            {!! $alumni->qrCodeSvg(4) !!}
          </div>
          <div class="small text-muted-slate mt-1">Live QR Code</div>
        </div>
        <div class="col-md-9 d-flex flex-column justify-content-center">
          <h3 class="h6 mb-1">Digital Alumni Verification Card Active</h3>
          <p class="small text-muted-slate mb-2">
            The QR Code links to the secure real-time verification endpoint. Scanning displays public graduate authenticity details directly from the department registry.
          </p>
          <div class="small">
            <strong>Verification URL:</strong>
            <code class="text-break">{{ $alumni->qrVerificationUrl() }}</code>
          </div>
        </div>
      </div>
    @endif

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="d-flex gap-2">
        @if ($alumni->verification_status !== 'verified')
          <form method="POST" action="{{ route('admin.alumni-verifications.approve', $alumni) }}" onsubmit="return confirm('Confirm approval for {{ addslashes($alumni->full_name) }}? A Digital Alumni ID and QR Token will be generated.');">
            @csrf
            <button type="submit" class="btn btn-brass">
              ✓ Approve &amp; Issue Digital Alumni ID
            </button>
          </form>
        @else
          <span class="text-success small fw-bold">✓ This alumni account is verified and active.</span>
        @endif
      </div>

      <div>
        @if ($alumni->verification_status !== 'rejected')
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            ✗ Reject Verification
          </button>

          <!-- Reject Modal -->
          <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST" action="{{ route('admin.alumni-verifications.reject', $alumni) }}">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title h6" id="rejectModalLabel">Reject Alumni Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p class="small text-muted-slate mb-3">
                      Please enter a reason for rejecting the verification request for <strong>{{ $alumni->full_name }}</strong>. This reason will be displayed on the alumnus dashboard.
                    </p>
                    <div class="mb-3">
                      <label class="form-label small" for="reason">Reason for rejection</label>
                      <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="e.g., Information mismatch with official department records."></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-ink" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection
