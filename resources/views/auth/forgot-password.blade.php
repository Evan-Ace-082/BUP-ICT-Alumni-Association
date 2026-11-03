@extends('layouts.auth')

@section('title', 'Forgot Password — BUP ICT Alumni Association')

@section('content')
  <div class="d-flex align-items-center justify-content-center mb-3">
    <span class="badge bg-light text-dark border px-3 py-1 small fw-semibold">
      <i class="bi bi-shield-lock me-1 text-brass"></i> Account Recovery
    </span>
  </div>

  <h1 class="h4 mb-1 text-center">Reset your password</h1>
  <p class="text-muted-slate small mb-4 text-center">Enter your registered email address to receive a 6-digit verification code.</p>

  @if (session('status'))
    <div class="alert alert-success small mb-3">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small mb-3">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small fw-semibold" for="email">Email address</label>
      <input type="email" name="email" class="form-control" id="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
    </div>
    <button type="submit" class="btn btn-brass w-100 mb-2">Send Verification Code</button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    <a href="{{ route('login') }}">&larr; Back to log in</a>
  </p>
@endsection
