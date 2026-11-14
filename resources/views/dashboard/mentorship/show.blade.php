@extends('layouts.dashboard')
@section('title', ($mentor->alumni?->full_name ?? $mentor->user->name) . ' — Mentor Profile')
@section('page_title', 'Mentor Profile')

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

  <div class="row g-4">
    {{-- Left column: Header & Basic Information --}}
    <div class="col-lg-4">
      <div class="card-feature text-center mb-4">
        @if ($mentor->alumni?->profile_photo)
          <img src="{{ asset('storage/' . $mentor->alumni->profile_photo) }}" alt="{{ $mentor->alumni->full_name }}" class="rounded-circle img-thumbnail mb-3" style="width: 110px; height: 110px; object-fit: cover;">
        @else
          <div class="rounded-circle bg-brass-subtle text-brass d-inline-flex align-items-center justify-content-center fw-bold mb-3" style="width: 110px; height: 110px; font-size: 38px;">
            {{ substr($mentor->alumni?->full_name ?? $mentor->user->name, 0, 1) }}
          </div>
        @endif

        <h2 class="h5 mb-1">{{ $mentor->alumni?->full_name ?? $mentor->user->name }}</h2>
        <div class="fw-semibold text-ink-900 mb-1">{{ $mentor->current_designation }}</div>
        <div class="small text-muted-slate mb-2">{{ $mentor->company }} &middot; {{ $mentor->city }}, {{ $mentor->country }}</div>

        <div class="d-flex justify-content-center gap-2 mb-3">
          <span class="badge bg-brass-subtle text-brass">{{ $mentor->alumni?->batch?->batch_code }}</span>
          <span class="badge bg-light border text-muted-slate">Class of {{ $mentor->alumni?->graduation_year ?? 'N/A' }}</span>
          <span class="badge bg-light border text-muted-slate">{{ $mentor->years_of_experience }}+ Years Exp</span>
        </div>

        @if ($mentor->linkedin_url)
          <div class="mb-3">
            <a href="{{ $mentor->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-ink btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/></svg>
              LinkedIn Profile
            </a>
          </div>
        @endif

        <div class="border-top pt-3 text-start small">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Mentorship Mode:</span>
            <span class="fw-semibold">{{ ucfirst($mentor->mentorship_mode) }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Language:</span>
            <span class="fw-semibold">{{ $mentor->preferred_language }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Frequency:</span>
            <span class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $mentor->mentorship_frequency)) }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Capacity Status:</span>
            @if ($mentor->availability_status === 'available')
              <span class="badge bg-success-subtle text-success">Available</span>
            @elseif ($mentor->availability_status === 'limited')
              <span class="badge bg-warning-subtle text-warning">Limited ({{ $mentor->remaining_slots }} slot left)</span>
            @else
              <span class="badge bg-secondary-subtle text-secondary">Full / Unavailable</span>
            @endif
          </div>
        </div>
      </div>

      <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-outline-ink btn-sm w-100">&larr; Back to Mentors Directory</a>
    </div>

    {{-- Right column: Bio, Categories, and Request Form --}}
    <div class="col-lg-8">
      <div class="card-feature mb-4">
        <h3 class="h6 mb-2">About the Mentor</h3>
        <p class="small text-muted-slate mb-4" style="line-height: 1.6; white-space: pre-line;">{{ $mentor->bio }}</p>

        <h3 class="h6 mb-2">Mentoring Categories</h3>
        <div class="d-flex flex-wrap gap-2 mb-4">
          @foreach ($mentor->categories as $category)
            <div class="border rounded p-2 bg-paper-subtle d-flex align-items-center gap-2">
              <span class="badge bg-brass text-white">&check;</span>
              <div>
                <div class="small fw-semibold text-ink-900">{{ $category->name }}</div>
                <div class="small text-muted-slate" style="font-size: 11px;">{{ $category->description }}</div>
              </div>
            </div>
          @endforeach
        </div>

        @if ($mentor->topics_offered)
          <h3 class="h6 mb-2">Topics &amp; Guidance Offered</h3>
          <p class="small text-muted-slate mb-4">{{ $mentor->topics_offered }}</p>
        @endif

        @if ($mentor->availability_schedule)
          <h3 class="h6 mb-2">Schedule &amp; Availability</h3>
          <p class="small text-muted-slate mb-4">{{ $mentor->availability_schedule }}</p>
        @endif
      </div>

      {{-- Request Mentorship Section --}}
      <div class="card-feature">
        <h3 class="h6 mb-3">Request Mentorship from {{ $mentor->alumni?->full_name ?? $mentor->user->name }}</h3>

        @if (!$canRequest['allowed'])
          <div class="alert alert-light border small">
            <strong>Notice:</strong> {{ $canRequest['reason'] }}
          </div>
        @else
          <form method="POST" action="{{ route('dashboard.mentorship.request.mentor', $mentor) }}">
            @csrf
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Mentorship Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select form-select-sm" required>
                  <option value="">Select a category</option>
                  @foreach ($mentor->categories as $c)
                    <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Specific Topic or Question <span class="text-danger">*</span></label>
                <input type="text" name="specific_topic" class="form-control form-control-sm" placeholder="e.g. CV Review, Technical Interview Strategy" value="{{ old('specific_topic') }}" required>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Reason for Requesting Mentorship</label>
                <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Why would you like to connect with this mentor specifically?">{{ old('reason') }}</textarea>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Career or Learning Goals</label>
                <textarea name="career_goals" rows="2" class="form-control form-control-sm" placeholder="What are your goals for the next 6-12 months?">{{ old('career_goals') }}</textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Preferred Mode</label>
                <select name="preferred_mode" class="form-select form-select-sm">
                  <option value="online">Online (Google Meet / Zoom)</option>
                  <option value="offline">In-Person</option>
                  <option value="both">Flexible / Both</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Preferred Meeting Schedule</label>
                <input type="text" name="preferred_schedule" class="form-control form-control-sm" placeholder="e.g. Friday evenings, Weekends" value="{{ old('preferred_schedule') }}">
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Additional Message</label>
                <textarea name="message" rows="3" class="form-control form-control-sm" placeholder="Any additional context or questions you would like to share...">{{ old('message') }}</textarea>
              </div>

              <div class="col-12 pt-2">
                <button type="submit" class="btn btn-brass">Submit Mentorship Request</button>
              </div>
            </div>
          </form>
        @endif
      </div>
    </div>
  </div>
@endsection
