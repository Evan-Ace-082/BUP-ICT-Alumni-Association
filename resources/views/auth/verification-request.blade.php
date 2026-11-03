@extends('layouts.auth')

@section('title', 'Request Verification — BUP ICT Alumni Association')

@section('content')
  <h1 class="h4 mb-1">We couldn't find your record</h1>
  <p class="text-muted-slate small mb-4">Submit your details and a department admin will review your request.</p>

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('verification.store') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="student_id">Student ID</label>
      <input type="text" name="student_id" id="student_id" class="form-control" value="{{ old('student_id', $studentId) }}">
    </div>
    <div class="mb-3">
      <label class="form-label small" for="full_name">Full name</label>
      <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}" required>
    </div>
    <div class="mb-3">
      <label class="form-label small" for="email">Email</label>
      <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="mb-3">
      <label class="form-label small" for="phone">Phone</label>
      <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="mb-3">
      <label class="form-label small" for="message">Additional information</label>
      <textarea name="message" id="message" class="form-control" rows="3">{{ old('message') }}</textarea>
    </div>
    <button type="submit" class="btn btn-brass w-100">Submit request</button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    Already have an account? <a href="{{ route('login') }}">Log in</a>
  </p>
@endsection
