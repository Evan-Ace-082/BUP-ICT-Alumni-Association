@extends('layouts.dashboard')
@section('title', 'Edit Job Listing — Admin — BUP ICT Alumni Association')
@section('page_title', 'Edit Job Listing')
@section('content')
  <a href="{{ route('admin.jobs.index') }}" class="small d-inline-block mb-3">&larr; Back to Job Management</a>

  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="h6 mb-1">Edit Job: {{ $job->title }}</h2>
        <p class="small text-muted-slate mb-0">Posted by {{ $job->poster->name ?? 'Unknown' }} on {{ $job->created_at->format('M d, Y') }}</p>
      </div>
      <div>
        @if ($job->status === 'published')
          <span class="badge bg-success">Published</span>
        @elseif ($job->status === 'draft')
          <span class="badge bg-secondary">Draft</span>
        @else
          <span class="badge bg-dark">Closed</span>
        @endif
      </div>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger small mb-3">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('admin.jobs.update', $job) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Job Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="{{ old('title', $job->title) }}" required>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Company / Organization <span class="text-danger">*</span></label>
          <input type="text" name="company" class="form-control" value="{{ old('company', $job->company) }}" required>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Employment Type <span class="text-danger">*</span></label>
          <select name="employment_type" class="form-select" required>
            @foreach (\App\Models\JobListing::EMPLOYMENT_TYPES as $type)
              <option value="{{ $type }}" @selected(old('employment_type', $job->employment_type) === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            <option value="published" @selected(old('status', $job->status) === 'published')>Published</option>
            <option value="draft" @selected(old('status', $job->status) === 'draft')>Draft</option>
            <option value="closed" @selected(old('status', $job->status) === 'closed')>Closed</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Location</label>
          <input type="text" name="location" class="form-control" value="{{ old('location', $job->location) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Country</label>
          <input type="text" name="country" class="form-control" value="{{ old('country', $job->country) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Salary / Compensation (Optional)</label>
          <input type="text" name="salary" class="form-control" value="{{ old('salary', $job->salary) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application URL</label>
          <input type="url" name="application_url" class="form-control" value="{{ old('application_url', $job->application_url) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application Email</label>
          <input type="email" name="application_email" class="form-control" value="{{ old('application_email', $job->application_email) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application Deadline</label>
          <input type="date" name="deadline" class="form-control" value="{{ old('deadline', $job->deadline ? $job->deadline->format('Y-m-d') : '') }}">
        </div>

        <div class="col-12">
          <label class="form-label small fw-semibold">Job Description <span class="text-danger">*</span></label>
          <textarea name="description" class="form-control" rows="6" required>{{ old('description', $job->description) }}</textarea>
        </div>

        <div class="col-12 mt-4 pt-2 border-top d-flex gap-2">
          <button type="submit" class="btn btn-brass">Save Changes</button>
          <a href="{{ route('admin.jobs.index') }}" class="btn btn-link text-muted-slate text-decoration-none ms-auto">Cancel</a>
        </div>
      </div>
    </form>
  </div>
@endsection
