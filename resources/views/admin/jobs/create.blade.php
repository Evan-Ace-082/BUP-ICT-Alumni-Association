@extends('layouts.dashboard')
@section('title', 'Add Job Listing — Admin — BUP ICT Alumni Association')
@section('page_title', 'Add Job Listing')
@section('content')
  <a href="{{ route('admin.jobs.index') }}" class="small d-inline-block mb-3">&larr; Back to Job Management</a>

  <div class="card-feature">
    <div class="mb-4">
      <h2 class="h6 mb-1">Create a New Job Listing</h2>
      <p class="small text-muted-slate mb-0">Publish an opportunity to the alumni Job Board or save it as a draft.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger small mb-3">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('admin.jobs.store') }}">
      @csrf

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Job Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Senior Software Engineer" required>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Company / Organization <span class="text-danger">*</span></label>
          <input type="text" name="company" class="form-control" value="{{ old('company') }}" placeholder="e.g. Brain Station 23" required>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Employment Type <span class="text-danger">*</span></label>
          <select name="employment_type" class="form-select" required>
            @foreach (\App\Models\JobListing::EMPLOYMENT_TYPES as $type)
              <option value="{{ $type }}" @selected(old('employment_type', 'Full Time') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Location</label>
          <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g. Dhaka, Bangladesh (or Remote)">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Country</label>
          <input type="text" name="country" class="form-control" value="{{ old('country') }}" placeholder="e.g. Bangladesh">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Salary / Compensation (Optional)</label>
          <input type="text" name="salary" class="form-control" value="{{ old('salary') }}" placeholder="e.g. Negotiable / 80k - 120k BDT">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application URL</label>
          <input type="url" name="application_url" class="form-control" value="{{ old('application_url') }}" placeholder="https://example.com/apply">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application Email</label>
          <input type="email" name="application_email" class="form-control" value="{{ old('application_email') }}" placeholder="jobs@company.com">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Application Deadline</label>
          <input type="date" name="deadline" class="form-control" value="{{ old('deadline') }}">
        </div>

        <div class="col-12">
          <label class="form-label small fw-semibold">Job Description <span class="text-danger">*</span></label>
          <textarea name="description" class="form-control" rows="6" placeholder="Provide detailed job requirements, responsibilities, and how alumni can apply..." required>{{ old('description') }}</textarea>
        </div>

        <div class="col-12 mt-4 pt-2 border-top d-flex gap-2">
          <button type="submit" name="status" value="published" class="btn btn-brass">Publish Job</button>
          <button type="submit" name="status" value="draft" class="btn btn-outline-ink">Save as Draft</button>
          <a href="{{ route('admin.jobs.index') }}" class="btn btn-link text-muted-slate text-decoration-none ms-auto">Cancel</a>
        </div>
      </div>
    </form>
  </div>
@endsection
