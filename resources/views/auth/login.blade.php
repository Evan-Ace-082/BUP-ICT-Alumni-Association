@extends('layouts.auth')

@section('title', 'Log in — BUP ICT Alumni Association')

@section('content')
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1">Welcome back</h1>
    <p class="text-muted-slate small mb-0">Sign in to your verified alumni account.</p>
  </div>

  @if (session('status'))
    <div class="alert alert-success small py-2">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small py-2">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small fw-semibold" for="email">Email address</label>
      <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label small fw-semibold mb-0" for="password">Password</label>
        <a href="{{ route('password.request') }}" class="small text-muted-slate text-decoration-none">Forgot password?</a>
      </div>
      <div class="input-group">
        <input type="password" name="password" id="password" class="form-control border-end-0" placeholder="••••••••" required>
        <button class="btn btn-outline-secondary border-start-0 text-muted-slate px-3" style="background-color: rgba(9, 16, 30, 0.85); border-color: rgba(255, 255, 255, 0.16);" type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; this.innerText = p.type === 'password' ? 'Show' : 'Hide';">
          Show
        </button>
      </div>
    </div>

    <div class="form-check mb-4">
      <input type="checkbox" name="remember" id="remember" class="form-check-input">
      <label class="form-check-label small text-muted-slate" for="remember">Keep me logged in on this device</label>
    </div>

    <button type="submit" class="btn btn-brass w-100 py-2">Sign in to Dashboard</button>
  </form>

  <div class="mt-4 pt-3 border-top text-center">
    <p class="small text-muted-slate mb-0">
      Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-brass text-decoration-none">Join the Association</a>
    </p>
  </div>
@endsection
