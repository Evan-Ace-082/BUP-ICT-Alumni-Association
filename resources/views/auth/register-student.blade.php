@extends('layouts.auth')

@section('title', 'Student Registration — BUP ICT Mentorship')

@section('content')
  <div class="mb-3">
    <span class="badge bg-brass-subtle text-brass mb-2">BUP ICT Mentorship</span>
    <h1 class="h4 mb-1">Current Student Registration</h1>
    <p class="text-muted-slate small mb-0">Register your student account to connect with verified senior alumni mentors.</p>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('register.student') }}">
    @csrf

    <div class="mb-3">
      <label class="form-label small fw-semibold" for="student_id">Student ID <span class="text-danger">*</span></label>
      <input type="text" name="student_id" id="student_id" class="form-control" value="{{ old('student_id') }}" placeholder="e.g. 210204001" required autofocus>
      <div class="form-text">Your official BUP student identification number.</div>
    </div>

    <div class="mb-3">
      <label class="form-label small fw-semibold" for="full_name">Full Name <span class="text-danger">*</span></label>
      <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}" placeholder="e.g. Nabil Hasan" required>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="email">Email Address <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="name@bup.edu.bd" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="phone">Phone Number</label>
        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="+8801XXXXXXXXX">
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="session">Academic Session <span class="text-danger">*</span></label>
        <input type="text" name="session" id="session" class="form-control" value="{{ old('session') }}" placeholder="e.g. 2021-2022" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="batch_id">Batch (Optional)</label>
        <select name="batch_id" id="batch_id" class="form-select">
          <option value="">Select your batch cohort</option>
          @foreach ($batches as $b)
            <option value="{{ $b->id }}" {{ old('batch_id') == $b->id ? 'selected' : '' }}>
              {{ $b->batch_code }} &mdash; {{ $b->batch_name }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="enrollment_year">Enrollment Year <span class="text-danger">*</span></label>
        <input type="number" name="enrollment_year" id="enrollment_year" class="form-control" value="{{ old('enrollment_year', date('Y') - 2) }}" min="2015" max="{{ date('Y') + 1 }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="expected_graduation_year">Expected Graduation <span class="text-danger">*</span></label>
        <input type="number" name="expected_graduation_year" id="expected_graduation_year" class="form-control" value="{{ old('expected_graduation_year', date('Y') + 2) }}" min="{{ date('Y') }}" max="{{ date('Y') + 10 }}" required>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="password">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" id="password" class="form-control" required minlength="8">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
      </div>
    </div>

    <div class="alert alert-info small mb-3">
      <strong>Verification Notice:</strong> Student registrations are reviewed by the department administrator against the official roster before full mentorship access is enabled.
    </div>

    <button type="submit" class="btn btn-brass w-100">Register as Current Student</button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    Graduated alumni? <a href="{{ route('register') }}">Register as Alumni</a> &middot; <a href="{{ route('login') }}">Log in</a>
  </p>
@endsection
