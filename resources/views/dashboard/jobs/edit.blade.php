@extends('layouts.dashboard')
@section('title', 'Edit Job — BUP ICT Alumni Association')
@section('page_title', 'Edit Job')
@section('content')
  <a href="{{ route('dashboard.jobs.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('dashboard.jobs.update', $job) }}">
      @csrf @method('PUT')
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title', $job->title) }}" required></div>
      <div class="mb-3"><label class="form-label small">Company</label><input type="text" name="company" class="form-control" value="{{ old('company', $job->company) }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description', $job->description) }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Employment Type</label>
        <select name="employment_type" class="form-select">
          @foreach (\App\Models\JobListing::EMPLOYMENT_TYPES as $type)<option value="{{ $type }}" @selected(old('employment_type', $job->employment_type) === $type)>{{ $type }}</option>@endforeach
        </select>
      </div>
      <button class="btn btn-brass">Save Changes</button>
    </form>
  </div>
@endsection
