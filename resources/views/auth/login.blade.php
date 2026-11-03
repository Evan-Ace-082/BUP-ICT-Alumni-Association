@extends('layouts.auth')

@section('title', 'Log in — BUP ICT Alumni Association')

@section('content')
  <h1 class="h4 mb-1">Welcome back</h1>
  <p class="text-muted-slate small mb-4">Log in to your alumni account.</p>

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

  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="email">Email address</label>
      <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label small" for="password">Password</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>
    <div class="form-check mb-3">
      <input type="checkbox" name="remember" id="remember" class="form-check-input">
      <label class="form-check-label small" for="remember">Remember me</label>
    </div>
    <button type="submit" class="btn btn-brass w-100">Log in</button>
  </form>

  <p class="text-center small text-muted-slate mt-3 mb-0">
    <a href="{{ route('password.request') }}">Forgot your password?</a>
  </p>
  <p class="text-center small text-muted-slate mt-2 mb-0">
    Don't have an account? <a href="{{ route('register') }}">Join the Association</a>
  </p>
@endsection
