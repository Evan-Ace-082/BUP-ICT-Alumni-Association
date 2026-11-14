@extends('layouts.dashboard')
@section('title', 'Find a Mentor — BUP ICT Alumni Association')
@section('page_title', 'Find a Mentor')

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

  {{-- Top Navigation & Action Banner --}}
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h5 mb-1 text-ink-900">Find a Mentor</h1>
      <p class="small text-muted-slate mb-0">
        Connect with verified senior BUP ICT alumni for career guidance, research mentorship, interview prep, and industry insights.
      </p>
    </div>
    <div class="d-flex gap-2">
      @if (auth()->check() && $userEligibility)
        @if ($userEligibility['can_apply_as_mentor'] && !auth()->user()->mentorProfile)
          <a href="{{ route('dashboard.mentorship.become-mentor') }}" class="btn btn-brass btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Become a Mentor
          </a>
        @elseif (auth()->user()->mentorProfile)
          <a href="{{ route('dashboard.mentorship.mentor') }}" class="btn btn-outline-ink btn-sm">
            Mentor Dashboard
          </a>
        @endif
        <a href="{{ route('dashboard.mentorship.mentee') }}" class="btn btn-outline-ink btn-sm">
          My Mentorships
        </a>
      @endif
    </div>
  </div>

  {{-- Mentee Eligibility Notification (if not eligible to find a mentor) --}}
  @if (auth()->check() && $userEligibility && !$userEligibility['can_find_mentor'])
    <div class="alert alert-light border small mb-4 d-flex align-items-center gap-2">
      <span class="badge bg-secondary">Viewing Mode</span>
      <span>You are viewing mentors as an alumnus. Direct mentorship requests are reserved for current students and the newest graduating cohort ({{ $userEligibility['latest_graduation_year'] ?? 'Latest' }}).</span>
    </div>
  @endif

  {{-- Advanced Search & Filter Card (Section 6) --}}
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('dashboard.mentorship.index') }}" id="mentorFilterForm">
      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <label class="form-label small text-muted-slate mb-1">Search Keywords</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, designation, company, skills..." value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small text-muted-slate mb-1">Batch</label>
          <select name="batch_id" class="form-select form-select-sm">
            <option value="">All Batches</option>
            @foreach ($batches as $batch)
              <option value="{{ $batch->id }}" {{ ($filters['batch_id'] ?? '') == $batch->id ? 'selected' : '' }}>
                {{ $batch->batch_code }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small text-muted-slate mb-1">Graduation Year</label>
          <input type="number" name="graduation_year" class="form-control form-control-sm" placeholder="e.g. 2020" value="{{ $filters['graduation_year'] ?? '' }}">
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small text-muted-slate mb-1">Company</label>
          <input type="text" name="company" class="form-control form-control-sm" placeholder="e.g. Google" value="{{ $filters['company'] ?? '' }}">
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small text-muted-slate mb-1">Mode</label>
          <select name="mode" class="form-select form-select-sm">
            <option value="all">All Modes</option>
            <option value="online" {{ ($filters['mode'] ?? '') === 'online' ? 'selected' : '' }}>Online</option>
            <option value="offline" {{ ($filters['mode'] ?? '') === 'offline' ? 'selected' : '' }}>In-Person</option>
          </select>
        </div>
      </div>

      {{-- Mentorship Categories Multi-Filter (Section 6) --}}
      <div class="mb-3">
        <label class="form-label small text-muted-slate d-block mb-1">Filter by Mentorship Categories (Multiple selection)</label>
        <div class="d-flex flex-wrap gap-1">
          @php $selectedCats = (array) ($filters['categories'] ?? []); @endphp
          @foreach ($categories as $cat)
            <div class="form-check form-check-inline m-0">
              <input class="btn-check" type="checkbox" name="categories[]" id="filter_cat_{{ $cat->id }}" value="{{ $cat->id }}" {{ in_array($cat->id, $selectedCats) ? 'checked' : '' }} onchange="document.getElementById('mentorFilterForm').submit()">
              <label class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px; border-radius: 999px;" for="filter_cat_{{ $cat->id }}">
                {{ $cat->name }}
              </label>
            </div>
          @endforeach
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div class="small text-muted-slate">
          Showing {{ $mentors->total() > 0 ? $mentors->total() : $legacyMentors->total() }} mentors
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-ghost-sm btn-sm">Reset Filters</a>
          <button type="submit" class="btn btn-brass btn-sm px-3">Filter Mentors</button>
        </div>
      </div>
    </form>
  </div>

  {{-- Mentors Grid (Section 6) --}}
  @if ($mentors->isEmpty() && $legacyMentors->isEmpty())
    <div class="card-feature text-center py-5">
      <div class="mb-2 text-muted-slate">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3 class="h6 mb-1">No Mentors Found</h3>
      <p class="small text-muted-slate mb-3">Try adjusting your category selection or search keywords.</p>
      <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-outline-ink btn-sm">Clear All Filters</a>
    </div>
  @else
    <div class="row g-3 mb-4">
      {{-- Approved Mentor Profile Cards --}}
      @foreach ($mentors as $mentor)
        <div class="col-md-6 col-lg-4">
          <div class="card-feature h-100 d-flex flex-column justify-content-between">
            <div>
              {{-- Top Row: Photo & Basic Details --}}
              <div class="d-flex gap-3 align-items-start mb-3">
                @if ($mentor->alumni?->profile_photo)
                  <img src="{{ asset('storage/' . $mentor->alumni->profile_photo) }}" alt="{{ $mentor->alumni->full_name }}" class="rounded-circle img-thumbnail flex-shrink-0" style="width: 54px; height: 54px; object-fit: cover;">
                @else
                  <div class="rounded-circle bg-brass-subtle text-brass d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 54px; height: 54px; font-size: 20px;">
                    {{ substr($mentor->alumni?->full_name ?? $mentor->user->name, 0, 1) }}
                  </div>
                @endif
                <div class="overflow-hidden">
                  <h3 class="h6 mb-0 text-truncate" title="{{ $mentor->alumni?->full_name ?? $mentor->user->name }}">
                    {{ $mentor->alumni?->full_name ?? $mentor->user->name }}
                  </h3>
                  <div class="small text-ink-900 fw-semibold text-truncate">{{ $mentor->current_designation }}</div>
                  <div class="small text-muted-slate text-truncate">{{ $mentor->company }}</div>
                  <div class="small text-muted-slate" style="font-size: 11px;">
                    {{ $mentor->alumni?->batch?->batch_code }} &middot; Graduated {{ $mentor->alumni?->graduation_year ?? 'N/A' }}
                  </div>
                </div>
              </div>

              {{-- Badges Row --}}
              <div class="d-flex flex-wrap gap-1 mb-2">
                @if ($mentor->availability_status === 'available')
                  <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Available</span>
                @elseif ($mentor->availability_status === 'limited')
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 10px;">Limited Capacity (1 left)</span>
                @elseif ($mentor->availability_status === 'fully_booked')
                  <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">Fully Booked</span>
                @else
                  <span class="badge bg-secondary" style="font-size: 10px;">Unavailable</span>
                @endif
                <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">{{ ucfirst($mentor->mentorship_mode) }}</span>
                <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">{{ $mentor->years_of_experience }} yrs exp</span>
              </div>

              {{-- Categories tags --}}
              <div class="mb-3">
                <div class="small text-muted-slate mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase;">Mentoring In:</div>
                <div class="d-flex flex-wrap gap-1">
                  @foreach ($mentor->categories->take(3) as $cat)
                    <span class="badge bg-brass-subtle text-brass" style="font-size: 10px; font-weight: 500;">
                      {{ $cat->name }}
                    </span>
                  @endforeach
                  @if ($mentor->categories->count() > 3)
                    <span class="badge bg-light text-muted-slate border" style="font-size: 10px;">+{{ $mentor->categories->count() - 3 }} more</span>
                  @endif
                </div>
              </div>

              {{-- Bio preview --}}
              <p class="small text-muted-slate mb-3" style="font-size: 12px; line-height: 1.4;">
                {{ Str::limit($mentor->bio, 110) }}
              </p>
            </div>

            {{-- Card Actions --}}
            <div class="pt-2 border-top d-flex gap-2">
              <a href="{{ route('dashboard.mentorship.show', $mentor) }}" class="btn btn-outline-ink btn-sm flex-fill">View Profile</a>
              <button type="button" class="btn btn-brass btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#requestModal{{ $mentor->id }}" {{ !$mentor->isAcceptingRequests() ? 'disabled' : '' }}>
                Request
              </button>
            </div>
          </div>
        </div>

        {{-- Request Mentorship Modal (Section 7) --}}
        <div class="modal fade" id="requestModal{{ $mentor->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $mentor->id }}" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <form method="POST" action="{{ route('dashboard.mentorship.request.mentor', $mentor) }}">
                @csrf
                <div class="modal-header">
                  <h5 class="modal-title h6" id="modalLabel{{ $mentor->id }}">
                    Request Mentorship from {{ $mentor->alumni?->full_name ?? $mentor->user->name }}
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Mentorship Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select form-select-sm" required>
                      <option value="">Select Category</option>
                      @foreach ($mentor->categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Specific Topic or Question <span class="text-danger">*</span></label>
                    <input type="text" name="specific_topic" class="form-control form-control-sm" placeholder="e.g. CV Review for Senior Dev role, GRE application tips" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Requesting Mentorship</label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Why did you choose this mentor?"></textarea>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Career or Learning Goals</label>
                    <textarea name="career_goals" rows="2" class="form-control form-control-sm" placeholder="What are your goals for the next 6-12 months?"></textarea>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label small fw-semibold">Preferred Mode</label>
                      <select name="preferred_mode" class="form-select form-select-sm">
                        <option value="online">Online</option>
                        <option value="offline">In-Person</option>
                        <option value="both">Flexible / Both</option>
                      </select>
                    </div>
                    <div class="col-6">
                      <label class="form-label small fw-semibold">Preferred Schedule</label>
                      <input type="text" name="preferred_schedule" class="form-control form-control-sm" placeholder="e.g. Weekends, Fri evening">
                    </div>
                  </div>

                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Additional Message</label>
                    <textarea name="message" rows="2" class="form-control form-control-sm" placeholder="Brief note of introduction..."></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-brass btn-sm">Submit Request</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      @endforeach

      {{-- Legacy Alumni fallback rendering (for tests) --}}
      @foreach ($legacyMentors as $legacyAlumni)
        <div class="col-md-6 col-lg-4">
          <div class="card-feature h-100 d-flex flex-column justify-content-between">
            <div>
              <h3 class="h6 mb-1">{{ $legacyAlumni->full_name }}</h3>
              <p class="small text-muted-slate mb-1">{{ $legacyAlumni->job_title ?? 'Alumni' }} &middot; {{ $legacyAlumni->company }}</p>
              <p class="small text-muted-slate mb-2">{{ $legacyAlumni->batch?->batch_code }}</p>
            </div>
            <form method="POST" action="{{ route('dashboard.mentorship.request', $legacyAlumni) }}" class="mt-2">
              @csrf
              <input type="text" name="message" class="form-control form-control-sm mb-2" placeholder="Short message (optional)">
              <button class="btn btn-sm btn-brass w-100">Request Mentorship</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $mentors->total() > 0 ? $mentors->links() : $legacyMentors->links() }}
    </div>
  @endif
@endsection
