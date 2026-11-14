@extends('layouts.dashboard')
@section('title', 'Become a Mentor — BUP ICT Alumni Association')
@section('page_title', 'Become a Mentor')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
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

  @if ($profile && $profile->status === \App\Models\MentorProfile::STATUS_PENDING)
    <div class="card-feature mb-4 border-warning">
      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-warning text-dark p-2">Application Under Review</span>
        <div>
          <h2 class="h6 mb-1">Your Mentor Application Is Pending Administrative Review</h2>
          <p class="small text-muted-slate mb-0">
            Thank you for applying to guide the next generation of BUP ICT students and graduates.
            Department administrators are reviewing your submission. You will receive an email once approved.
          </p>
        </div>
      </div>
    </div>
  @endif

  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h2 class="h5 mb-1">Senior Alumni Mentor Registration</h2>
        <p class="text-muted-slate small mb-0">
          Share your industry knowledge, career insights, and guidance with currently enrolled BUP ICT students and newest graduates.
        </p>
      </div>
      <span class="badge bg-brass-subtle text-brass">Cohort Eligibility: Verified Senior Alumni</span>
    </div>

    <form method="POST" action="{{ route('dashboard.mentorship.store-profile') }}" id="mentorRegistrationForm">
      @csrf

      {{-- Section A: Basic Identity (Read-only from verified official alumni record) --}}
      <div class="border rounded p-3 mb-4 bg-paper-subtle">
        <h3 class="h6 text-ink-900 mb-3 d-flex align-items-center gap-2">
          <span class="badge bg-brass text-white" style="width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">A</span>
          Official Alumni Identity (Verified)
        </h3>
        <p class="small text-muted-slate mb-3">These official identity credentials cannot be modified through the mentorship form.</p>

        <div class="row g-3">
          <div class="col-md-2 text-center">
            @if ($alumni->profile_photo)
              <img src="{{ asset('storage/' . $alumni->profile_photo) }}" alt="{{ $alumni->full_name }}" class="rounded-circle img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
            @else
              <div class="rounded-circle bg-brass-subtle text-brass d-inline-flex align-items-center justify-content-center fw-bold" style="width: 80px; height: 80px; font-size: 28px;">
                {{ substr($alumni->full_name, 0, 1) }}
              </div>
            @endif
          </div>
          <div class="col-md-10">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small text-muted-slate mb-0">Full Name</label>
                <div class="fw-semibold">{{ $alumni->full_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted-slate mb-0">Student ID</label>
                <div class="fw-semibold">{{ $alumni->student_id }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted-slate mb-0">Batch &amp; Graduation</label>
                <div class="fw-semibold">{{ $alumni->batch?->batch_code }} &middot; Graduated {{ $alumni->graduation_year ?? 'N/A' }}</div>
              </div>
              <div class="col-md-6 mt-2">
                <label class="form-label small text-muted-slate mb-0">Registered Email</label>
                <div class="small">{{ $user->email }}</div>
              </div>
              <div class="col-md-6 mt-2">
                <label class="form-label small text-muted-slate mb-0">Contact Phone (Private)</label>
                <div class="small">{{ $alumni->phone ?? ($user->phone ?? 'Not set') }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Section B: Professional Information --}}
      <div class="border rounded p-3 mb-4">
        <h3 class="h6 text-ink-900 mb-3 d-flex align-items-center gap-2">
          <span class="badge bg-brass text-white" style="width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">B</span>
          Professional Background
        </h3>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="current_designation">Current Designation / Job Title <span class="text-danger">*</span></label>
            <input type="text" name="current_designation" id="current_designation" class="form-control" value="{{ old('current_designation', $profile?->current_designation ?? $alumni->job_title) }}" placeholder="e.g. Senior Software Engineer" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="company">Company or Institution <span class="text-danger">*</span></label>
            <input type="text" name="company" id="company" class="form-control" value="{{ old('company', $profile?->company ?? $alumni->company) }}" placeholder="e.g. Google, BUP, Brain Station 23" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="industry">Industry / Sector</label>
            <input type="text" name="industry" id="industry" class="form-control" value="{{ old('industry', $profile?->industry) }}" placeholder="e.g. Information Technology, FinTech, Academia">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="years_of_experience">Years of Professional Experience <span class="text-danger">*</span></label>
            <input type="number" name="years_of_experience" id="years_of_experience" class="form-control" value="{{ old('years_of_experience', $profile?->years_of_experience ?? 3) }}" min="0" max="50" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="linkedin_url">LinkedIn Profile URL</label>
            <input type="url" name="linkedin_url" id="linkedin_url" class="form-control" value="{{ old('linkedin_url', $profile?->linkedin_url) }}" placeholder="https://linkedin.com/in/username">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="country">Country <span class="text-danger">*</span></label>
            <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $profile?->country ?? ($alumni->country ?? 'Bangladesh')) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="city">City <span class="text-danger">*</span></label>
            <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $profile?->city ?? ($alumni->city ?? 'Dhaka')) }}" required>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold" for="bio">Professional Biography <span class="text-danger">*</span></label>
            <textarea name="bio" id="bio" rows="3" class="form-control" placeholder="Provide a summary of your professional journey, technical expertise, and career milestones..." required>{{ old('bio', $profile?->bio) }}</textarea>
            <div class="form-text">Displayed on your public mentor profile card.</div>
          </div>
        </div>
      </div>

      {{-- Section C: Mentorship Information & Categories --}}
      <div class="border rounded p-3 mb-4">
        <h3 class="h6 text-ink-900 mb-2 d-flex align-items-center gap-2">
          <span class="badge bg-brass text-white" style="width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">C</span>
          Mentorship Expertise &amp; Preferences
        </h3>
        <p class="small text-muted-slate mb-3">
          <strong>Important:</strong> Your mentorship categories are independent of your current job sector. Select all areas you are qualified and enthusiastic to guide others on.
        </p>

        <div class="mb-4">
          <label class="form-label small fw-semibold d-block mb-2">Mentorship Categories (Select at least one) <span class="text-danger">*</span></label>
          <div class="row g-2">
            @php $selectedCats = old('categories', $profile ? $profile->categories->pluck('id')->toArray() : []); @endphp
            @foreach ($categories as $cat)
              <div class="col-md-6 col-lg-4">
                <div class="form-check p-2 border rounded h-100 bg-white" style="cursor: pointer;">
                  <input class="form-check-input ms-1" type="checkbox" name="categories[]" value="{{ $cat->id }}" id="cat_{{ $cat->id }}" {{ in_array($cat->id, $selectedCats) ? 'checked' : '' }}>
                  <label class="form-check-label ps-2 small fw-semibold d-block" for="cat_{{ $cat->id }}" style="cursor: pointer;">
                    {{ $cat->name }}
                    <span class="d-block text-muted-slate fw-normal" style="font-size: 11px;">{{ Str::limit($cat->description, 60) }}</span>
                  </label>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="expertise_areas">Key Expertise Areas &amp; Skills</label>
            <input type="text" name="expertise_areas" id="expertise_areas" class="form-control" value="{{ old('expertise_areas', $profile?->expertise_areas) }}" placeholder="e.g. Distributed Systems, LeetCode, GRE prep, System Design">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold" for="topics_offered">Specific Topics You Can Guide On</label>
            <input type="text" name="topics_offered" id="topics_offered" class="form-control" value="{{ old('topics_offered', $profile?->topics_offered) }}" placeholder="e.g. CV reviews, mock technical interviews, scholarship applications">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="preferred_mentee_audience">Preferred Mentee Audience <span class="text-danger">*</span></label>
            <select name="preferred_mentee_audience" id="preferred_mentee_audience" class="form-select" required>
              <option value="both" {{ old('preferred_mentee_audience', $profile?->preferred_mentee_audience) === 'both' ? 'selected' : '' }}>Both Students &amp; Recent Graduates</option>
              <option value="students" {{ old('preferred_mentee_audience', $profile?->preferred_mentee_audience) === 'students' ? 'selected' : '' }}>Current BUP ICT Students Only</option>
              <option value="graduates" {{ old('preferred_mentee_audience', $profile?->preferred_mentee_audience) === 'graduates' ? 'selected' : '' }}>Latest Graduating Cohort Only</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="mentorship_mode">Mentorship Mode <span class="text-danger">*</span></label>
            <select name="mentorship_mode" id="mentorship_mode" class="form-select" required>
              <option value="online" {{ old('mentorship_mode', $profile?->mentorship_mode) === 'online' ? 'selected' : '' }}>Online (Google Meet / Zoom)</option>
              <option value="offline" {{ old('mentorship_mode', $profile?->mentorship_mode) === 'offline' ? 'selected' : '' }}>In-Person / Offline</option>
              <option value="both" {{ old('mentorship_mode', $profile?->mentorship_mode) === 'both' ? 'selected' : '' }}>Both Online &amp; Offline</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="preferred_language">Preferred Language <span class="text-danger">*</span></label>
            <select name="preferred_language" id="preferred_language" class="form-select" required>
              <option value="Both" {{ old('preferred_language', $profile?->preferred_language) === 'Both' ? 'selected' : '' }}>Both English &amp; Bengali</option>
              <option value="English" {{ old('preferred_language', $profile?->preferred_language) === 'English' ? 'selected' : '' }}>English</option>
              <option value="Bengali" {{ old('preferred_language', $profile?->preferred_language) === 'Bengali' ? 'selected' : '' }}>Bengali</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="max_mentees">Maximum Active Mentees <span class="text-danger">*</span></label>
            <input type="number" name="max_mentees" id="max_mentees" class="form-control" value="{{ old('max_mentees', $profile?->max_mentees ?? 3) }}" min="1" max="15" required>
            <div class="form-text">Capacity limit for simultaneously active accepted mentees.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="mentorship_frequency">Mentorship Frequency <span class="text-danger">*</span></label>
            <select name="mentorship_frequency" id="mentorship_frequency" class="form-select" required>
              <option value="flexible" {{ old('mentorship_frequency', $profile?->mentorship_frequency) === 'flexible' ? 'selected' : '' }}>Flexible</option>
              <option value="one_time" {{ old('mentorship_frequency', $profile?->mentorship_frequency) === 'one_time' ? 'selected' : '' }}>One-Time Consultation</option>
              <option value="weekly" {{ old('mentorship_frequency', $profile?->mentorship_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
              <option value="biweekly" {{ old('mentorship_frequency', $profile?->mentorship_frequency) === 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
              <option value="monthly" {{ old('mentorship_frequency', $profile?->mentorship_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold" for="availability_schedule">Available Days &amp; Time</label>
            <input type="text" name="availability_schedule" id="availability_schedule" class="form-control" value="{{ old('availability_schedule', $profile?->availability_schedule) }}" placeholder="e.g. Friday 7-9 PM, Weekends">
          </div>
        </div>
      </div>

      {{-- Section D: Personal Statement --}}
      <div class="border rounded p-3 mb-4">
        <h3 class="h6 text-ink-900 mb-2 d-flex align-items-center gap-2">
          <span class="badge bg-brass text-white" style="width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">D</span>
          Personal Statement
        </h3>
        <label class="form-label small fw-semibold" for="personal_statement">
          Why would you like to become a mentor, and how can you help the next generation of BUP ICT graduates? <span class="text-danger">*</span>
        </label>
        <textarea name="personal_statement" id="personal_statement" rows="4" class="form-control" placeholder="Share your motivation and vision for mentoring BUP ICT juniors..." required>{{ old('personal_statement', $profile?->personal_statement) }}</textarea>
      </div>

      <div class="d-flex justify-content-between align-items-center pt-2">
        <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-outline-ink btn-sm">Cancel</a>
        <button type="submit" class="btn btn-brass">Submit Application for Review</button>
      </div>
    </form>
  </div>
@endsection
