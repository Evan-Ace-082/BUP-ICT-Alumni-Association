@extends('layouts.auth')

@section('title', 'Verify Code — BUP ICT Alumni Association')

@section('content')
  <div class="d-flex align-items-center justify-content-center mb-3">
    <span class="badge bg-light text-dark border px-3 py-1 small fw-semibold">
      <i class="bi bi-envelope-check me-1 text-brass"></i> Step 2 of 3: Verify Identity
    </span>
  </div>

  <h1 class="h4 mb-1 text-center">Enter Verification Code</h1>
  <p class="text-muted-slate small mb-4 text-center">
    We sent a 6-digit verification code to <strong>{{ $email }}</strong>. It expires in <strong>5 minutes</strong>.
  </p>

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

  <form method="POST" action="{{ route('password.otp.verify') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small fw-semibold" for="otp_code">6-Digit Verification Code</label>
      <input type="text"
             name="otp_code"
             id="otp_code"
             class="form-control text-center fs-4 fw-bold"
             inputmode="numeric"
             maxlength="6"
             pattern="[0-9]{6}"
             required
             autofocus
             placeholder="------"
             style="letter-spacing: 8px;">
    </div>
    <button type="submit" class="btn btn-brass w-100 mb-2">Verify Code</button>
  </form>

  <form method="POST" action="{{ route('password.otp.resend') }}" class="mb-2">
    @csrf
    <button type="submit" id="resendBtn" class="btn btn-outline-ink w-100" @if($cooldownRemaining > 0) disabled @endif>
      <span id="resendText">
        @if($cooldownRemaining > 0)
          Resend code in {{ $cooldownRemaining }}s
        @else
          Resend Code
        @endif
      </span>
    </button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    <a href="{{ route('password.request') }}">&larr; Request code for a different email</a>
  </p>

  @if($cooldownRemaining > 0)
    <script>
      (function() {
        let remaining = {{ $cooldownRemaining }};
        const btn = document.getElementById('resendBtn');
        const text = document.getElementById('resendText');
        const timer = setInterval(function() {
          remaining--;
          if (remaining <= 0) {
            clearInterval(timer);
            btn.removeAttribute('disabled');
            text.textContent = 'Resend Code';
          } else {
            text.textContent = 'Resend code in ' + remaining + 's';
          }
        }, 1000);
      })();
    </script>
  @endif
@endsection
