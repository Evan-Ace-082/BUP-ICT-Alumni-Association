@extends('layouts.auth')

@section('title', 'Verify your email — BUP ICT Alumni Association')

@section('content')
  <h1 class="h4 mb-1">Check your email</h1>
  <p class="text-muted-slate small mb-4">We sent a 6-digit code to {{ $email }}.</p>

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

  <form method="POST" action="{{ route('register.otp') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="otp_code">Verification code</label>
      <input type="text" name="otp_code" id="otp_code" class="form-control" inputmode="numeric" maxlength="6" required autofocus>
    </div>
    <button type="submit" class="btn btn-brass w-100">Verify &amp; create account</button>
  </form>

  <form method="POST" action="{{ route('register.otp.resend') }}" class="mt-2">
    @csrf
    <button type="submit" class="btn btn-outline-ink w-100">Resend code</button>
  </form>
@endsection
