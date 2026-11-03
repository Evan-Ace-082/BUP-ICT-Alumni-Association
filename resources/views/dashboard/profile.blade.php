@extends('layouts.dashboard')

@section('title', 'My Profile — BUP ICT Alumni Association')
@section('page_title', 'My Profile')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  <div class="card-feature mb-4">
    <h2 class="h6 mb-3">Official record (not editable here)</h2>
    <div class="row small">
      <div class="col-md-4">
        <span class="text-muted-slate">Student ID</span>
        <div class="fw-semibold">{{ $alumni->student_id ?? '—' }}</div>
      </div>
      <div class="col-md-4">
        <span class="text-muted-slate">Name</span>
        <div class="fw-semibold">{{ $alumni->full_name ?? '—' }}</div>
      </div>
      <div class="col-md-4">
        <span class="text-muted-slate">Batch</span>
        <div class="fw-semibold">{{ $alumni->batch->batch_name ?? '—' }}</div>
      </div>
    </div>
    <p class="small text-muted-slate mb-0 mt-2">Contact an administrator if any of this is incorrect.</p>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <h2 class="h6 mb-3">Account</h2>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label small" for="email">Login email</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="phone">Phone</label>
        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', auth()->user()->phone) }}">
      </div>
    </div>

    <h2 class="h6 mb-3">Profile</h2>
    <div class="row g-3 mb-3">
      <div class="col-12">
        <label class="form-label small d-block">Profile photo</label>
        @if ($alumni?->profile_photo)
          <img src="{{ asset('storage/'.$alumni->profile_photo) }}" alt="Current profile photo" class="rounded mb-2" style="width:96px;height:96px;object-fit:cover;">
        @endif
        <input type="file" name="photo" id="photo" class="form-control" accept="image/png,image/jpeg,image/webp">
        <div class="form-text">JPEG, PNG, or WebP. Max 2048 KB.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="current_institution">Current job / institution</label>
        <input type="text" name="current_institution" id="current_institution" class="form-control" value="{{ old('current_institution', $alumni->current_institution ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="designation">Designation / position</label>
        <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation', $alumni->designation ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="company">Company</label>
        <input type="text" name="company" id="company" class="form-control" value="{{ old('company', $alumni->company ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="higher_studies">Higher studies</label>
        <input type="text" name="higher_studies" id="higher_studies" class="form-control" value="{{ old('higher_studies', $alumni->higher_studies ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="linkedin_url">LinkedIn</label>
        <input type="url" name="linkedin_url" id="linkedin_url" class="form-control" value="{{ old('linkedin_url', $alumni->linkedin_url ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="facebook_url">Facebook</label>
        <input type="url" name="facebook_url" id="facebook_url" class="form-control" value="{{ old('facebook_url', $alumni->facebook_url ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="blood_group">Blood group</label>
        <input type="text" name="blood_group" id="blood_group" class="form-control" value="{{ old('blood_group', $alumni->blood_group ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="country">Country</label>
        <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $alumni->country ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="city">City</label>
        <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $alumni->city ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label small" for="job_status">Job status</label>
        <select name="job_status" id="job_status" class="form-select">
          <option value="">— Not specified —</option>
          @foreach (\App\Models\Alumni::JOB_STATUSES as $status)
            <option value="{{ $status }}" @selected(old('job_status', $alumni->job_status ?? '') === $status)>{{ $status }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12">
        <label class="form-label small" for="skills">Skills</label>
        <textarea name="skills" id="skills" class="form-control" rows="2" placeholder="e.g. PHP, Laravel, Project Management">{{ old('skills', $alumni->skills ?? '') }}</textarea>
        <div class="form-text">Comma-separated list of skills.</div>
      </div>
      <div class="col-12 form-check">
        <input type="hidden" name="mentorship" value="0">
        <input type="checkbox" name="mentorship" id="mentorship" value="1" class="form-check-input"
               @checked(old('mentorship', $alumni->mentorship ?? false))>
        <label class="form-check-label small" for="mentorship">I'm willing to provide mentorship to fellow alumni</label>
      </div>
      <div class="col-12">
        <label class="form-label small" for="address">Address</label>
        <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $alumni->address ?? '') }}</textarea>
      </div>
    </div>

    <button type="submit" class="btn btn-brass">Save changes</button>
  </form>
@endsection
