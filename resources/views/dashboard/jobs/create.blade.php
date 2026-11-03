@extends('layouts.dashboard')
@section('title', 'Post a Job — BUP ICT Alumni Association')
@section('page_title', 'Post a Job')
@section('content')
  <a href="{{ route('dashboard.jobs.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('dashboard.jobs.store') }}">
      @csrf
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
      <div class="mb-3"><label class="form-label small">Company</label><input type="text" name="company" class="form-control" value="{{ old('company') }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Employment Type</label>
        <select name="employment_type" class="form-select">
          @foreach (\App\Models\JobListing::EMPLOYMENT_TYPES as $type)<option value="{{ $type }}" @selected(old('employment_type') === $type)>{{ $type }}</option>@endforeach
        </select>
      </div>
      <div class="mb-3"><label class="form-label small">Location</label><input type="text" name="location" class="form-control" value="{{ old('location') }}"></div>
      <div class="mb-3"><label class="form-label small">Application URL</label><input type="url" name="application_url" class="form-control" value="{{ old('application_url') }}"></div>
      <div class="mb-3"><label class="form-label small">Deadline</label><input type="date" name="deadline" class="form-control" value="{{ old('deadline') }}"></div>
      <button class="btn btn-brass">Post Job</button>
    </form>
  </div>
@endsection
