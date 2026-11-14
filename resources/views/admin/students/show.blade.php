@extends('layouts.dashboard')
@section('title', "Student Details: {$student->full_name} — Admin Panel")
@section('page_title', 'Student Verification Details')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('admin.students.index') }}" class="small text-muted-slate text-decoration-none">
          &larr; Back to Students List
        </a>
      </div>
      <h1 class="h5 mb-0">{{ $student->full_name }} ({{ $student->student_id }})</h1>
    </div>
    <div class="d-flex gap-2">
      @if ($student->verification_status === 'verified')
        <span class="badge bg-success align-self-center px-3 py-2">Verified Student</span>
      @elseif ($student->verification_status === 'pending')
        <span class="badge bg-warning text-dark align-self-center px-3 py-2">Pending Verification</span>
      @else
        <span class="badge bg-secondary align-self-center px-3 py-2">Rejected</span>
      @endif

      @if ($student->status === 'graduated')
        <span class="badge bg-primary align-self-center px-3 py-2">Graduated</span>
      @endif
    </div>
  </div>

  <div class="row g-4">
    {{-- Left column: Student Profile & Academic Details --}}
    <div class="col-lg-7">
      <div class="card-feature mb-4">
        <h2 class="h6 text-brass fw-bold text-uppercase mb-3" style="letter-spacing: 0.05em; font-size: 12px;">
          Academic &amp; Registration Information
        </h2>
        <div class="row g-3 small">
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Full Name</span>
            <span class="fw-semibold text-ink-900">{{ $student->full_name }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Student ID</span>
            <span class="fw-semibold text-ink-900">{{ $student->student_id }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Official Email</span>
            <span class="fw-semibold text-ink-900">{{ $student->email }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Phone Number</span>
            <span class="fw-semibold text-ink-900">{{ $student->phone ?: 'Not provided' }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Academic Session</span>
            <span class="fw-semibold text-ink-900">{{ $student->session ?: 'Not set' }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Assigned Batch</span>
            <span class="fw-semibold text-ink-900">{{ $student->batch ? $student->batch->batch_code . ' — ' . $student->batch->title : 'Unassigned' }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Enrollment Year</span>
            <span class="fw-semibold text-ink-900">{{ $student->enrollment_year ?: '—' }}</span>
          </div>
          <div class="col-sm-6">
            <span class="text-muted-slate d-block">Expected Graduation Year</span>
            <span class="fw-semibold text-ink-900">{{ $student->expected_graduation_year ?: '—' }}</span>
          </div>
        </div>

        @if ($student->admin_notes)
          <div class="mt-4 pt-3 border-top">
            <span class="text-muted-slate small d-block mb-1">Administrative Notes</span>
            <div class="p-3 bg-light rounded small text-ink-900" style="white-space: pre-wrap;">{{ $student->admin_notes }}</div>
          </div>
        @endif

        @if ($student->rejection_reason)
          <div class="mt-3 pt-3 border-top">
            <span class="text-danger small fw-semibold d-block mb-1">Rejection Reason</span>
            <div class="p-3 bg-light border border-danger-subtle rounded small text-danger">{{ $student->rejection_reason }}</div>
          </div>
        @endif
      </div>

      {{-- User account info --}}
      <div class="card-feature">
        <h2 class="h6 text-brass fw-bold text-uppercase mb-3" style="letter-spacing: 0.05em; font-size: 12px;">
          Linked User Account
        </h2>
        @if ($student->user)
          <div class="row g-3 small">
            <div class="col-sm-6">
              <span class="text-muted-slate d-block">Account Name</span>
              <span class="fw-semibold text-ink-900">{{ $student->user->name }}</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted-slate d-block">Login Email</span>
              <span class="fw-semibold text-ink-900">{{ $student->user->email }}</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted-slate d-block">Email Verified</span>
              <span>{{ $student->user->hasVerifiedEmail() ? 'Verified (' . $student->user->email_verified_at->format('M d, Y') . ')' : 'Pending verification' }}</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted-slate d-block">Assigned Roles</span>
              <div>
                @foreach ($student->user->roles as $role)
                  <span class="badge bg-secondary me-1">{{ $role->name }}</span>
                @endforeach
              </div>
            </div>
          </div>
        @else
          <p class="small text-muted-slate mb-0">No active login account linked to this student record.</p>
        @endif
      </div>
    </div>

    {{-- Right column: Verification and Transition Actions --}}
    <div class="col-lg-5">
      {{-- Verification Decision (if pending or rejected) --}}
      @if (!$student->isVerified())
        <div class="card-feature mb-4 border-primary">
          <h2 class="h6 fw-bold text-ink-900 mb-2">Review &amp; Verify</h2>
          <p class="small text-muted-slate mb-3">
            Verifying this student allows them to log in as a student mentee and request guidance from approved senior alumni mentors.
          </p>

          <form method="POST" action="{{ route('admin.students.verify', $student) }}" class="mb-3">
            @csrf
            @method('PATCH')
            <div class="mb-3">
              <label class="form-label small text-muted-slate">Admin Notes (optional)</label>
              <textarea name="admin_notes" class="form-control form-control-sm" rows="2" placeholder="e.g., ID card verified via registrar record.">{{ old('admin_notes', $student->admin_notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-brass btn-sm w-100" onclick="return confirm('Verify student {{ $student->full_name }}?')">
              Verify Student Account
            </button>
          </form>

          <hr class="my-3">

          <form method="POST" action="{{ route('admin.students.reject', $student) }}">
            @csrf
            @method('PATCH')
            <div class="mb-2">
              <label class="form-label small text-danger fw-semibold">Rejection Reason (required)</label>
              <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" required placeholder="Explain why the student record cannot be verified...">{{ old('rejection_reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Reject this student registration?')">
              Reject Student Registration
            </button>
          </form>
        </div>
      @endif

      {{-- Transition to Alumnus (Section 11) --}}
      @if ($student->isVerified())
        @if ($student->status === 'graduated')
          <div class="card-feature border-success mb-4">
            <h2 class="h6 fw-bold text-success mb-2">Graduated Alumnus</h2>
            <p class="small text-muted-slate mb-0">
              This student has successfully transitioned to an official verified Alumni record. Their account now retains alumni privileges across the platform.
            </p>
          </div>
        @else
          <div class="card-feature border-warning">
            <h2 class="h6 fw-bold text-ink-900 mb-2">Transition to Alumnus</h2>
            <p class="small text-muted-slate mb-3">
              When this student completes their degree, transition their profile into an official verified Alumnus record. Their linked user account will receive the <strong>Alumni</strong> role automatically without creating duplicate accounts.
            </p>

            <form method="POST" action="{{ route('admin.students.transition', $student) }}">
              @csrf
              <div class="mb-3">
                <label class="form-label small fw-semibold">Graduation Year <span class="text-danger">*</span></label>
                <input type="number" name="graduation_year" class="form-control form-control-sm" required min="2015" max="{{ now()->year }}" value="{{ old('graduation_year', $student->expected_graduation_year ?: now()->year) }}">
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Alumni Batch <span class="text-danger">*</span></label>
                <select name="batch_id" class="form-select form-select-sm" required>
                  <option value="">Select Alumni Batch...</option>
                  @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" {{ (old('batch_id', $student->batch_id) == $batch->id) ? 'selected' : '' }}>
                      {{ $batch->batch_code }} — {{ $batch->title }}
                    </option>
                  @endforeach
                </select>
              </div>

              <button type="submit" class="btn btn-primary btn-sm w-100" onclick="return confirm('Graduate and transition {{ $student->full_name }} to official Alumnus status?')">
                Graduate &amp; Transition to Alumnus
              </button>
            </form>
          </div>
        @endif
      @endif
    </div>
  </div>
@endsection
