@extends('layouts.dashboard')

@section('title', 'Add Alumni Information — BUP ICT Alumni Association')
@section('page_title', 'Add Alumni Information')


@section('content')
  <div class="mb-3">
    <a href="{{ route('admin.alumni-network.index') }}" class="small text-muted-slate text-decoration-none">
      &larr; Back to Alumni Network Management
    </a>
  </div>

  @if (session('existing_alumni_id'))
    <div class="alert alert-warning small mb-4 d-flex justify-content-between align-items-center">
      <span>This Student ID already exists in the system. Would you like to edit that record instead?</span>
      <a href="{{ route('admin.alumni-network.edit', session('existing_alumni_id')) }}" class="btn btn-sm btn-brass">
        Edit Existing Record
      </a>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-feature mb-4">
    <h2 class="h6 mb-2">Create New Alumni Information</h2>
    <p class="small text-muted-slate mb-4">
      Add an alumni profile to the system. The record will be created in <span class="badge bg-light text-dark border">unclaimed</span> status,
      preserving the standard user account registration and claim workflow.
    </p>

    <form method="POST" action="{{ route('admin.alumni-network.store') }}" enctype="multipart/form-data">
      @csrf

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Academic & Identity Information</h3>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label small fw-semibold" for="student_id">Student ID <span class="text-danger">*</span></label>
          <input
            type="text"
            name="student_id"
            id="student_id"
            class="form-control @error('student_id') is-invalid @enderror"
            placeholder="e.g. 1754101001"
            value="{{ old('student_id') }}"
            required
          >
          <div class="form-text">Must be unique across the alumni database.</div>
        </div>

        <div class="col-md-5">
          <label class="form-label small fw-semibold" for="full_name">Full Name <span class="text-danger">*</span></label>
          <input
            type="text"
            name="full_name"
            id="full_name"
            class="form-control @error('full_name') is-invalid @enderror"
            placeholder="e.g. A. B. M. Rahim"
            value="{{ old('full_name') }}"
            required
          >
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold" for="batch_id">Batch <span class="text-danger">*</span></label>
          <select name="batch_id" id="batch_id" class="form-select @error('batch_id') is-invalid @enderror" required>
            <option value="">Select batch</option>
            @foreach ($batches as $batch)
              <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>
                {{ $batch->batch_name }} ({{ $batch->batch_code }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small" for="graduation_year">Graduation Year</label>
          <input
            type="number"
            name="graduation_year"
            id="graduation_year"
            class="form-control @error('graduation_year') is-invalid @enderror"
            placeholder="e.g. 2022"
            min="1990"
            max="2100"
            value="{{ old('graduation_year') }}"
          >
        </div>

        <div class="col-md-9">
          <label class="form-label small" for="photo">Profile Photo</label>
          <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
          <div class="form-text">Optional. Max 2MB (JPEG, PNG, or WebP).</div>
        </div>
      </div>

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Location & Network Footprint</h3>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small" for="country">Country</label>
          <input
            type="text"
            name="country"
            id="country"
            class="form-control @error('country') is-invalid @enderror"
            placeholder="e.g. Bangladesh, United States, Germany"
            value="{{ old('country') }}"
          >
          <div class="form-text">Determines whether the alumnus appears in Alumni Abroad or Professionals in Bangladesh.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="city">City</label>
          <input
            type="text"
            name="city"
            id="city"
            class="form-control @error('city') is-invalid @enderror"
            placeholder="e.g. Dhaka, New York, Munich"
            value="{{ old('city') }}"
          >
        </div>
      </div>

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Career & Entrepreneurship</h3>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label small" for="job_status">Job Status</label>
          <select name="job_status" id="job_status" class="form-select @error('job_status') is-invalid @enderror">
            <option value="">— Select status —</option>
            @foreach ($jobStatuses as $status)
              <option value="{{ $status }}" @selected(old('job_status') === $status)>{{ $status }}</option>
            @endforeach
          </select>
          <div class="form-text">Choose 'Entrepreneur' for business founders or startups.</div>
        </div>

        <div class="col-md-4">
          <label class="form-label small" for="company">Company / Organization</label>
          <input
            type="text"
            name="company"
            id="company"
            class="form-control @error('company') is-invalid @enderror"
            placeholder="e.g. Brain Station 23, Google"
            value="{{ old('company') }}"
          >
        </div>

        <div class="col-md-4">
          <label class="form-label small" for="designation">Designation / Title</label>
          <input
            type="text"
            name="designation"
            id="designation"
            class="form-control @error('designation') is-invalid @enderror"
            placeholder="e.g. Founder & CEO, Senior Engineer"
            value="{{ old('designation') }}"
          >
        </div>
      </div>

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Higher Studies & Research</h3>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small" for="current_institution">Current Institution / University</label>
          <input
            type="text"
            name="current_institution"
            id="current_institution"
            class="form-control @error('current_institution') is-invalid @enderror"
            placeholder="e.g. National University of Singapore"
            value="{{ old('current_institution') }}"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="higher_studies">Degree / Research Program</label>
          <input
            type="text"
            name="higher_studies"
            id="higher_studies"
            class="form-control @error('higher_studies') is-invalid @enderror"
            placeholder="e.g. MSc in Computer Science, PhD Candidate"
            value="{{ old('higher_studies') }}"
          >
        </div>
      </div>

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Skills, Mentorship & Public Links</h3>
      <div class="row g-3 mb-4">
        <div class="col-12">
          <label class="form-label small" for="skills">Skills</label>
          <input
            type="text"
            name="skills"
            id="skills"
            class="form-control @error('skills') is-invalid @enderror"
            placeholder="e.g. Python, Machine Learning, AWS, Cloud Architecture"
            value="{{ old('skills') }}"
          >
          <div class="form-text">Comma-separated skills.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="linkedin_url">LinkedIn URL</label>
          <input
            type="url"
            name="linkedin_url"
            id="linkedin_url"
            class="form-control @error('linkedin_url') is-invalid @enderror"
            placeholder="https://linkedin.com/in/username"
            value="{{ old('linkedin_url') }}"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="facebook_url">Facebook URL</label>
          <input
            type="url"
            name="facebook_url"
            id="facebook_url"
            class="form-control @error('facebook_url') is-invalid @enderror"
            placeholder="https://facebook.com/username"
            value="{{ old('facebook_url') }}"
          >
        </div>

        <div class="col-12 form-check ps-4">
          <input type="hidden" name="mentorship" value="0">
          <input
            type="checkbox"
            name="mentorship"
            id="mentorship"
            value="1"
            class="form-check-input"
            @checked(old('mentorship') == '1')
          >
          <label class="form-check-label small" for="mentorship">Available to provide mentorship to fellow alumni</label>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.alumni-network.index') }}" class="btn btn-outline-ink">Cancel</a>
        <button type="submit" class="btn btn-brass">Save Alumni Information</button>
      </div>
    </form>
  </div>
@endsection
