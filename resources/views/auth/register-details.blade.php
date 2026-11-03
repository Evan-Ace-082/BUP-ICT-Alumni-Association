@extends('layouts.auth')

@section('title', 'Create your account — BUP ICT Alumni Association')

@section('content')
  <h1 class="h4 mb-1">Alumni record found</h1>
  <p class="text-muted-slate small mb-4">Confirm your details and create your login credentials.</p>

  <div class="placeholder-panel mb-3 text-start">
    <div class="small text-muted-slate">Name</div>
    <div class="fw-semibold mb-2">{{ $alumni->full_name }}</div>
    <div class="small text-muted-slate">Batch</div>
    <div class="fw-semibold">{{ $alumni->batch->batch_name ?? '—' }}</div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('register.details') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small" for="email">Email address</label>
      <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
      <div class="form-text">Doesn't have to match any email on file — this becomes your login email.</div>
    </div>
    <div class="mb-3">
      <label class="form-label small" for="phone">Phone number</label>
      <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="mb-3">
      <label class="form-label small" for="password">Password</label>
      <input type="password" name="password" id="password" class="form-control" required minlength="8">
    </div>
    <div class="mb-3">
      <label class="form-label small" for="password_confirmation">Confirm password</label>
      <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
    </div>
    <button type="submit" class="btn btn-brass w-100">Send verification code</button>
  </form>
@endsection
