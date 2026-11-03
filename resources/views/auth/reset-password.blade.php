@extends('layouts.auth')

@section('title', 'Set New Password — BUP ICT Alumni Association')

@section('content')
  <div class="d-flex align-items-center justify-content-center mb-3">
    <span class="badge bg-light text-dark border px-3 py-1 small fw-semibold">
      <i class="bi bi-key me-1 text-brass"></i> Step 3 of 3: New Password
    </span>
  </div>

  <h1 class="h4 mb-1 text-center">Create New Password</h1>
  <p class="text-muted-slate small mb-4 text-center">Set a secure password for <strong>{{ $email }}</strong>.</p>

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

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
      <label class="form-label small fw-semibold" for="password">New Password</label>
      <div class="input-group">
        <input type="password"
               name="password"
               id="password"
               class="form-control"
               required
               minlength="8"
               placeholder="Minimum 8 characters"
               autofocus>
        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password" aria-label="Toggle password visibility">
          <i class="bi bi-eye"></i>
        </button>
      </div>
      <div class="form-text">Must be at least 8 characters long.</div>
    </div>

    <div class="mb-3">
      <label class="form-label small fw-semibold" for="password_confirmation">Confirm New Password</label>
      <div class="input-group">
        <input type="password"
               name="password_confirmation"
               id="password_confirmation"
               class="form-control"
               required
               minlength="8"
               placeholder="Re-enter your new password">
        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation" aria-label="Toggle password visibility">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-brass w-100 mb-2">Reset Password</button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    <a href="{{ route('login') }}">&larr; Cancel and return to log in</a>
  </p>

  <script>
    document.querySelectorAll('.toggle-password').forEach(function(button) {
      button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      });
    });
  </script>
@endsection
