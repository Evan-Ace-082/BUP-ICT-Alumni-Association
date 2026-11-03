@extends('layouts.dashboard')

@section('title', 'Edit Alumni Network Information — BUP ICT Alumni Association')
@section('page_title', 'Edit Alumni Network Profile')


@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.alumni-network.index') }}" class="small text-muted-slate text-decoration-none">
      &larr; Back to Alumni Network Management
    </a>
    <a href="{{ route('alumni.show', $alumni) }}" target="_blank" class="btn btn-sm btn-outline-ink">
      🌐 View Public Profile
    </a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Official Record Card -->
  <div class="card-feature mb-4 bg-light border">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="small text-muted-slate">Student ID (Official Immutable Identity)</div>
        <div class="fs-5 fw-bold font-mono">{{ $alumni->student_id }}</div>
      </div>
      <div>
        <div class="small text-muted-slate">Current Account Status</div>
        <span class="badge bg-white text-dark border">{{ ucfirst($alumni->status) }}</span>
        @if ($alumni->user_id)
          <span class="badge bg-brass text-white ms-1">Linked Account</span>
        @else
          <span class="badge bg-light text-muted-slate border ms-1">Unclaimed</span>
        @endif
      </div>
      <div>
        <div class="small text-muted-slate">Network Categorization</div>
        <div class="d-flex gap-1 flex-wrap mt-1">
          @php($cats = $alumni->getCategories())
          @if (empty($cats))
            <span class="badge bg-light text-muted-slate border">Uncategorized</span>
          @else
            @foreach ($cats as $cat)
              <span class="badge bg-brass text-white">{{ $cat }}</span>
            @endforeach
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card-feature mb-4">
    <h2 class="h6 mb-3">Update Network & Professional Profile</h2>

    <form method="POST" action="{{ route('admin.alumni-network.update', $alumni) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <h3 class="h6 font-display border-bottom pb-2 mb-3">Identity & Academic Information</h3>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small fw-semibold" for="full_name">Full Name <span class="text-danger">*</span></label>
          <input
            type="text"
            name="full_name"
            id="full_name"
            class="form-control @error('full_name') is-invalid @enderror"
            value="{{ old('full_name', $alumni->full_name) }}"
            required
          >
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold" for="batch_id">Batch <span class="text-danger">*</span></label>
          <select name="batch_id" id="batch_id" class="form-select @error('batch_id') is-invalid @enderror" required>
            @foreach ($batches as $batch)
              <option value="{{ $batch->id }}" @selected(old('batch_id', $alumni->batch_id) == $batch->id)>
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
            min="1990"
            max="2100"
            value="{{ old('graduation_year', $alumni->graduation_year) }}"
          >
        </div>

        <div class="col-12">
          <label class="form-label small d-block">Profile Photo</label>
          <div class="d-flex align-items-center gap-3">
            <div class="rounded border overflow-hidden flex-shrink-0" style="width: 72px; height: 72px; aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
              @if ($alumni->profile_photo_url)
                <img src="{{ $alumni->profile_photo_url }}" alt="{{ $alumni->full_name }}" class="w-100 h-100 object-fit-cover">
              @else
                <div class="w-100 h-100 d-flex align-items-center justify-content-center fw-bold fs-4 text-muted-slate">
                  {{ substr($alumni->full_name, 0, 1) }}
                </div>
              @endif
            </div>
            <div class="flex-grow-1">
              <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
              <div class="form-text">Upload a new photo to replace the current one. JPEG, PNG, or WebP. Max 2048 KB.</div>
            </div>
          </div>
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
            placeholder="e.g. Bangladesh, Germany, United States"
            value="{{ old('country', $alumni->country) }}"
          >
          <div class="form-text">Used to categorize into <em>Alumni Abroad</em> (outside BD) or <em>Professionals in Bangladesh</em>.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="city">City</label>
          <input
            type="text"
            name="city"
            id="city"
            class="form-control @error('city') is-invalid @enderror"
            placeholder="e.g. Dhaka, Berlin, Toronto"
            value="{{ old('city', $alumni->city) }}"
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
              <option value="{{ $status }}" @selected(old('job_status', $alumni->job_status) === $status)>{{ $status }}</option>
            @endforeach
          </select>
          <div class="form-text">Select 'Entrepreneur' for business founders/co-founders.</div>
        </div>

        <div class="col-md-4">
          <label class="form-label small" for="company">Company / Organization</label>
          <input
            type="text"
            name="company"
            id="company"
            class="form-control @error('company') is-invalid @enderror"
            placeholder="e.g. Banglalink, Brain Station 23"
            value="{{ old('company', $alumni->company) }}"
          >
        </div>

        <div class="col-md-4">
          <label class="form-label small" for="designation">Designation / Role</label>
          <input
            type="text"
            name="designation"
            id="designation"
            class="form-control @error('designation') is-invalid @enderror"
            placeholder="e.g. Senior Software Engineer, Founder & CEO"
            value="{{ old('designation', $alumni->designation) }}"
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
            placeholder="e.g. University of Cambridge"
            value="{{ old('current_institution', $alumni->current_institution) }}"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label small" for="higher_studies">Degree / Higher Study Details</label>
          <input
            type="text"
            name="higher_studies"
            id="higher_studies"
            class="form-control @error('higher_studies') is-invalid @enderror"
            placeholder="e.g. MSc in Software Engineering, PhD Fellow"
            value="{{ old('higher_studies', $alumni->higher_studies) }}"
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
            placeholder="e.g. React, Laravel, DevOps, Cybersecurity"
            value="{{ old('skills', $alumni->skills) }}"
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
            value="{{ old('linkedin_url', $alumni->linkedin_url) }}"
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
            value="{{ old('facebook_url', $alumni->facebook_url) }}"
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
            @checked(old('mentorship', $alumni->mentorship) == 1)
          >
          <label class="form-check-label small" for="mentorship">Available to provide mentorship to fellow alumni</label>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.alumni-network.index') }}" class="btn btn-outline-ink">Cancel</a>
        <button type="submit" class="btn btn-brass">Save Changes</button>
      </div>
    </form>
  </div>
@endsection
