@extends('layouts.auth')

@section('title', 'Admin Two-Factor Authentication — BUP ICT Alumni Association')

@section('content')
  <div class="d-flex align-items-center justify-content-center mb-3">
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 small fw-semibold">
      <i class="bi bi-shield-lock-fill me-1"></i> Admin Two-Factor Security
    </span>
  </div>
  <h1 class="h4 mb-1 text-center">Verify Your Identity</h1>
  <p class="text-muted-slate small mb-4 text-center">
    A 6-digit security code has been sent to <strong>{{ $email }}</strong>. Enter the code below to complete sign in.
  </p>

  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('admin.login.otp.verify') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="otp_code">Security Code</label>
      <input type="text" name="otp_code" id="otp_code" class="form-control text-center fs-4 fw-bold" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="------" style="letter-spacing: 6px;">
    </div>
    <button type="submit" class="btn btn-brass w-100 mb-2">Verify &amp; Enter Admin Panel</button>
  </form>

  <form method="POST" action="{{ route('admin.login.otp.resend') }}" class="mb-2">
    @csrf
    <button type="submit" class="btn btn-outline-ink w-100">Resend Code</button>
  </form>

  <form method="POST" action="{{ route('admin.login.otp.cancel') }}" class="text-center">
    @csrf
    <button type="submit" class="btn btn-link text-muted-slate small p-0 text-decoration-none">
      &larr; Cancel and return to login
    </button>
  </form>
@endsection
