@extends('layouts.dashboard')
@section('title', 'New Admin — BUP ICT Alumni Association')
@section('page_title', 'New Admin')
@section('content')
  <a href="{{ route('admin.system.admins.index') }}" class="small d-inline-block mb-3">&larr; Back to Admin Management</a>
  <div class="card-feature">
    <p class="small text-muted-slate mb-3">This creates a new account with the Admin role only. Super Admin accounts cannot be created from this form.</p>
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.system.admins.store') }}">
      @csrf
      <div class="mb-3"><label class="form-label small">Name</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
      <div class="mb-3"><label class="form-label small">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
      <div class="mb-3"><label class="form-label small">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
      <div class="mb-3"><label class="form-label small">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required minlength="8"></div>
      <button class="btn btn-brass">Create Admin</button>
    </form>
  </div>
@endsection
