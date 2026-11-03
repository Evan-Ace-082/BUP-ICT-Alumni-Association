@extends('layouts.dashboard')

@section('title', 'Review Verification — '.$verificationRequest->full_name)
@section('page_title', 'Review Verification Request')


@section('content')
  <a href="{{ route('admin.verifications.index') }}" class="small d-inline-block mb-3">&larr; Back to Verification Requests</a>

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
        <h2 class="h5 mb-1">{{ $verificationRequest->full_name }}</h2>
        <p class="small text-muted-slate mb-0">Submitted on {{ $verificationRequest->created_at->format('F d, Y \a\t h:i A') }}</p>
      </div>
      <div>
        @if ($verificationRequest->status === 'pending')
          <span class="badge bg-warning text-dark">Pending Review</span>
        @elseif ($verificationRequest->status === 'approved')
          <span class="badge bg-success">Approved</span>
        @else
          <span class="badge bg-danger">Rejected</span>
        @endif
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="small text-muted-slate">Student ID</div>
        <div><strong>{{ $verificationRequest->student_id ?? 'Not provided' }}</strong></div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Email Address</div>
        <div>{{ $verificationRequest->email }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Phone Number</div>
        <div>{{ $verificationRequest->phone ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Assigned Batch</div>
        <div>{{ $verificationRequest->batch?->batch_name ?? 'Not assigned yet' }}</div>
      </div>
      <div class="col-12">
        <div class="small text-muted-slate">Additional Information / Message</div>
        <div class="p-2 bg-light rounded mt-1 small">
          {{ $verificationRequest->message ?: 'No additional message provided.' }}
        </div>
      </div>
    </div>

    @if ($existingAlumni)
      <div class="alert alert-info small mb-3">
        <strong>Existing Alumni Record Detected:</strong> An official alumni profile exists for Student ID <code>{{ $existingAlumni->student_id }}</code> ({{ $existingAlumni->full_name }}, Batch: {{ $existingAlumni->batch?->batch_name ?? '—' }}). Approving will update this record without creating a duplicate.
      </div>
    @endif

    @if ($existingUser)
      <div class="alert alert-info small mb-3">
        <strong>Existing User Account Detected:</strong> A user account is registered with email <code>{{ $existingUser->email }}</code>. Approving will automatically link the alumni record to this account and assign the Alumni role.
      </div>
    @endif

    @if ($verificationRequest->status === 'pending')
      <hr class="my-4">
      <h3 class="h6 mb-3">Process Request</h3>
      <div class="row g-3">
        <div class="col-md-8">
          <form method="POST" action="{{ route('admin.verifications.approve', $verificationRequest) }}">
            @csrf
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small" for="batch_id">Assign Batch <span class="text-danger">*</span></label>
                <select name="batch_id" id="batch_id" class="form-select" required>
                  <option value="">Select Official Batch</option>
                  @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" @selected(old('batch_id', $verificationRequest->batch_id) == $batch->id)>
                      {{ $batch->batch_name }} ({{ $batch->batch_code }})
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small" for="student_id">Confirm Student ID <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="student_id"
                  id="student_id"
                  class="form-control"
                  value="{{ old('student_id', $verificationRequest->student_id) }}"
                  required
                >
              </div>
              <div class="col-12 mt-3">
                <button type="submit" class="btn btn-brass">
                  ✓ Approve &amp; Verify Alumni
                </button>
              </div>
            </div>
          </form>
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-md-end">
          <form method="POST" action="{{ route('admin.verifications.reject', $verificationRequest) }}" onsubmit="return confirm('Are you sure you want to reject this verification request?');">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
              ✗ Reject Request
            </button>
          </form>
        </div>
      </div>
    @else
      <div class="border-top pt-3 mt-3 text-muted-slate small">
        Reviewed by <strong>{{ $verificationRequest->reviewer?->name ?? 'Administrator' }}</strong> on {{ $verificationRequest->reviewed_at?->format('F d, Y \a\t h:i A') ?? '—' }}.
      </div>
    @endif
  </div>
@endsection
