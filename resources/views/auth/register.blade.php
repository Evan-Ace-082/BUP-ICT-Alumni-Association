@extends('layouts.auth')

@section('title', 'Join the Association — BUP ICT Alumni Association')

@section('content')
  <h1 class="h4 mb-1">Join the Association</h1>
  <p class="text-muted-slate small mb-4">Enter your Student ID to find your official alumni record.</p>

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="student_id">Student ID</label>
      <input type="text" name="student_id" id="student_id" class="form-control" value="{{ old('student_id') }}" placeholder="e.g. 154677565" required autofocus>
      <div class="form-text">Numeric or alphanumeric — enter it exactly as issued.</div>
    </div>
    <button type="submit" class="btn btn-brass w-100">Find my record</button>
  </form>

  <div class="card p-3 bg-paper-subtle border text-center mt-3 mb-2" style="border-radius: var(--radius-sm);">
    <div class="small fw-semibold text-ink-900 mb-1">Currently Enrolled BUP ICT Student?</div>
    <div class="small text-muted-slate mb-2">Access the Mentorship System and career guidance by registering as a student.</div>
    <a href="{{ route('register.student') }}" class="btn btn-outline-ink btn-sm">Register as Current Student &rarr;</a>
  </div>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    Already have an account? <a href="{{ route('login') }}">Log in</a>
  </p>
@endsection
