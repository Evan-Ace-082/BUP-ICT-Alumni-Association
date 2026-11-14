@extends('layouts.dashboard')
@section('title', 'Mentor Dashboard — BUP ICT Alumni Association')
@section('page_title', 'Mentor Dashboard')

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

  {{-- Mentor Profile Status Banner --}}
  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        @if ($profile->alumni?->profile_photo)
          <img src="{{ asset('storage/' . $profile->alumni->profile_photo) }}" alt="{{ $profile->alumni->full_name }}" class="rounded-circle img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
        @else
          <div class="rounded-circle bg-brass-subtle text-brass d-flex align-items-center justify-content-center fw-bold" style="width: 60px; height: 60px; font-size: 24px;">
            {{ substr($profile->alumni?->full_name ?? $user->name, 0, 1) }}
          </div>
        @endif
        <div>
          <h1 class="h5 mb-0">{{ $profile->alumni?->full_name ?? $user->name }}</h1>
          <div class="small text-ink-900 fw-semibold">{{ $profile->current_designation }} at {{ $profile->company }}</div>
          <div class="small text-muted-slate">{{ $profile->alumni?->batch?->batch_code }} &middot; Class of {{ $profile->alumni?->graduation_year ?? 'N/A' }}</div>
        </div>
      </div>
      <div class="text-end">
        @if ($profile->status === \App\Models\MentorProfile::STATUS_APPROVED)
          <span class="badge bg-success p-2 mb-1 d-inline-block">Approved Active Mentor</span>
          <div class="small text-muted-slate">
            @if ($profile->is_paused)
              <span class="text-warning fw-semibold">&bull; Requests Paused</span>
            @else
              <span class="text-success fw-semibold">&bull; Accepting Requests</span>
            @endif
          </div>
        @elseif ($profile->status === \App\Models\MentorProfile::STATUS_PENDING)
          <span class="badge bg-warning text-dark p-2 mb-1 d-inline-block">Pending Admin Approval</span>
          <div class="small text-muted-slate">Application submitted on {{ $profile->created_at->format('M d, Y') }}</div>
        @elseif ($profile->status === \App\Models\MentorProfile::STATUS_SUSPENDED)
          <span class="badge bg-danger p-2 mb-1 d-inline-block">Profile Suspended</span>
          <div class="small text-muted-slate">Please contact administrator</div>
        @else
          <span class="badge bg-secondary p-2 mb-1 d-inline-block">Application Rejected</span>
        @endif
      </div>
    </div>
  </div>

  {{-- Metrics Row (Section 9) --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card-feature text-center h-100 p-3">
        <div class="small text-muted-slate mb-1">Incoming Requests</div>
        <div class="h4 mb-0 text-ink-900">{{ $incomingRequests->count() }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature text-center h-100 p-3">
        <div class="small text-muted-slate mb-1">Active Mentees</div>
        <div class="h4 mb-0 text-brass">{{ $activeMentees->count() }} / {{ $profile->max_mentees }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature text-center h-100 p-3">
        <div class="small text-muted-slate mb-1">Completed</div>
        <div class="h4 mb-0 text-success">{{ $completedMentorships->count() }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature text-center h-100 p-3">
        <div class="small text-muted-slate mb-1">Capacity Remaining</div>
        <div class="h4 mb-0 text-ink-900">{{ $profile->remaining_slots }} slots</div>
      </div>
    </div>
  </div>

  {{-- Navigation Tabs --}}
  <ul class="nav nav-tabs mb-4" id="mentorTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
        Incoming Requests ({{ $incomingRequests->count() }})
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="mentees-tab" data-bs-toggle="tab" data-bs-target="#mentees" type="button" role="tab">
        Active Mentees ({{ $activeMentees->count() }})
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
        Completed ({{ $completedMentorships->count() }})
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab">
        Availability &amp; Settings
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profileEdit" type="button" role="tab">
        Edit Mentoring Profile
      </button>
    </li>
  </ul>

  <div class="tab-content" id="mentorTabsContent">
    {{-- Tab 1: Incoming Requests --}}
    <div class="tab-pane fade show active" id="requests" role="tabpanel">
      @if ($incomingRequests->isEmpty())
        <div class="card-feature text-center py-4">
          <p class="small text-muted-slate mb-0">No pending mentorship requests at this time.</p>
        </div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($incomingRequests as $req)
            @php
              $menteeUser = $req->mentee;
              $menteeName = $menteeUser->alumni?->full_name ?? $menteeUser->student?->full_name ?? $menteeUser->name;
              $menteeBatch = $menteeUser->alumni?->batch?->batch_code ?? $menteeUser->student?->batch?->batch_code ?? 'Student';
            @endphp
            <div class="card-feature border">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div>
                  <h3 class="h6 mb-0">{{ $menteeName }}</h3>
                  <div class="small text-muted-slate">{{ $menteeBatch }} &middot; Requested {{ $req->created_at->diffForHumans() }}</div>
                </div>
                <span class="badge bg-brass-subtle text-brass">{{ $req->category?->name ?? 'Mentorship' }}</span>
              </div>

              @if ($req->specific_topic)
                <div class="mb-2">
                  <span class="small fw-semibold text-ink-900">Topic:</span>
                  <span class="small text-muted-slate">{{ $req->specific_topic }}</span>
                </div>
              @endif

              @if ($req->reason)
                <div class="mb-2">
                  <span class="small fw-semibold text-ink-900">Reason:</span>
                  <span class="small text-muted-slate">{{ $req->reason }}</span>
                </div>
              @endif

              @if ($req->career_goals)
                <div class="mb-2">
                  <span class="small fw-semibold text-ink-900">Goals:</span>
                  <span class="small text-muted-slate">{{ $req->career_goals }}</span>
                </div>
              @endif

              @if ($req->message)
                <div class="p-2 rounded bg-paper-subtle small mb-3">
                  <em>"{{ $req->message }}"</em>
                </div>
              @endif

              <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <div class="small text-muted-slate">
                  Mode: <strong>{{ ucfirst($req->preferred_mode) }}</strong> &middot; Schedule: <strong>{{ $req->preferred_schedule ?: 'Flexible' }}</strong>
                </div>
                <div class="d-flex gap-2">
                  <form method="POST" action="{{ route('dashboard.mentorship.requests.accept', $req) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-brass" {{ $profile->remaining_slots <= 0 ? 'disabled' : '' }}>
                      Accept Mentee
                    </button>
                  </form>
                  <button type="button" class="btn btn-sm btn-outline-ink" data-bs-toggle="modal" data-bs-target="#declineModal{{ $req->id }}">
                    Decline
                  </button>
                </div>
              </div>
            </div>

            {{-- Decline Modal --}}
            <div class="modal fade" id="declineModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" action="{{ route('dashboard.mentorship.requests.decline', $req) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                      <h5 class="modal-title h6">Decline Mentorship Request</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p class="small text-muted-slate mb-3">
                        Are you sure you want to decline this request from {{ $menteeName }}?
                        You can provide an optional note explaining your availability or constraints.
                      </p>
                      <label class="form-label small fw-semibold" for="decline_reason_{{ $req->id }}">Optional Reason / Note</label>
                      <textarea name="decline_reason" id="decline_reason_{{ $req->id }}" rows="3" class="form-control form-control-sm" placeholder="e.g. Currently preparing for professional exams, at full bandwidth this month..."></textarea>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-outline-danger btn-sm">Decline Request</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Tab 2: Active Mentees --}}
    <div class="tab-pane fade" id="mentees" role="tabpanel">
      @if ($activeMentees->isEmpty())
        <div class="card-feature text-center py-4">
          <p class="small text-muted-slate mb-0">No active mentees at this moment.</p>
        </div>
      @else
        <div class="row g-3">
          @foreach ($activeMentees as $menteeReq)
            @php
              $mUser = $menteeReq->mentee;
              $mName = $mUser->alumni?->full_name ?? $mUser->student?->full_name ?? $mUser->name;
              $mBatch = $mUser->alumni?->batch?->batch_code ?? $mUser->student?->batch?->batch_code ?? 'Student';
            @endphp
            <div class="col-md-6">
              <div class="card-feature h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h3 class="h6 mb-0">{{ $mName }}</h3>
                      <div class="small text-muted-slate">{{ $mBatch }} &middot; Connected {{ $menteeReq->responded_at?->diffForHumans() }}</div>
                    </div>
                    <span class="badge bg-success-subtle text-success">Active</span>
                  </div>

                  <div class="p-2 rounded bg-paper-subtle mb-3 small">
                    <div class="d-flex justify-content-between py-1">
                      <span class="text-muted-slate">Email:</span>
                      <a href="mailto:{{ $mUser->email }}" class="fw-semibold text-truncate">{{ $mUser->email }}</a>
                    </div>
                    @if ($mUser->phone || $mUser->alumni?->phone || $mUser->student?->phone)
                      <div class="d-flex justify-content-between py-1">
                        <span class="text-muted-slate">Phone:</span>
                        <span>{{ $mUser->phone ?? ($mUser->alumni?->phone ?? $mUser->student?->phone) }}</span>
                      </div>
                    @endif
                    <div class="d-flex justify-content-between py-1">
                      <span class="text-muted-slate">Category:</span>
                      <span class="fw-semibold">{{ $menteeReq->category?->name ?? 'General' }}</span>
                    </div>
                  </div>

                  @if ($menteeReq->specific_topic)
                    <p class="small text-muted-slate mb-2"><strong>Focus:</strong> {{ $menteeReq->specific_topic }}</p>
                  @endif
                </div>

                <div class="pt-3 border-top d-flex gap-2">
                  <a href="mailto:{{ $mUser->email }}" class="btn btn-outline-ink btn-sm flex-fill">Email Mentee</a>
                  <button type="button" class="btn btn-brass btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#completeModal{{ $menteeReq->id }}">
                    Mark Completed
                  </button>
                </div>
              </div>
            </div>

            {{-- Mark Completed Modal --}}
            <div class="modal fade" id="completeModal{{ $menteeReq->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" action="{{ route('dashboard.mentorship.requests.complete', $menteeReq) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                      <h5 class="modal-title h6">Complete Mentorship Engagement</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p class="small text-muted-slate mb-3">
                        Marking this mentorship as completed will free up one slot in your capacity ({{ $profile->active_mentees_count }} / {{ $profile->max_mentees }}).
                      </p>
                      <label class="form-label small fw-semibold" for="feedback_{{ $menteeReq->id }}">Closing Feedback or Notes (Optional)</label>
                      <textarea name="feedback" id="feedback_{{ $menteeReq->id }}" rows="3" class="form-control form-control-sm" placeholder="Any final advice or private notes..."></textarea>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-brass btn-sm">Confirm Completion</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Tab 3: Completed Mentorships --}}
    <div class="tab-pane fade" id="completed" role="tabpanel">
      @if ($completedMentorships->isEmpty())
        <div class="card-feature text-center py-4">
          <p class="small text-muted-slate mb-0">No completed mentorship records yet.</p>
        </div>
      @else
        <div class="table-responsive card-feature p-0">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="small text-muted-slate">
                <th>Mentee</th>
                <th>Category &amp; Topic</th>
                <th>Mode</th>
                <th>Completed On</th>
                <th>Feedback</th>
              </tr>
            </thead>
            <tbody class="small">
              @foreach ($completedMentorships as $cReq)
                @php
                  $cUser = $cReq->mentee;
                  $cName = $cUser->alumni?->full_name ?? $cUser->student?->full_name ?? $cUser->name;
                  $cBatch = $cUser->alumni?->batch?->batch_code ?? $cUser->student?->batch?->batch_code ?? 'Student';
                @endphp
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $cName }}</div>
                    <div class="text-muted-slate" style="font-size: 11px;">{{ $cBatch }}</div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $cReq->category?->name ?? 'General' }}</div>
                    <div class="text-muted-slate" style="font-size: 11px;">{{ $cReq->specific_topic }}</div>
                  </td>
                  <td>{{ ucfirst($cReq->preferred_mode) }}</td>
                  <td>{{ $cReq->completed_at?->format('M d, Y') ?? 'Completed' }}</td>
                  <td>{{ $cReq->feedback ?: '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- Tab 4: Availability & Capacity Settings (Section 8) --}}
    <div class="tab-pane fade" id="availability" role="tabpanel">
      <div class="card-feature">
        <h3 class="h6 mb-3">Availability &amp; Mentee Capacity Controls</h3>
        <form method="POST" action="{{ route('dashboard.mentorship.update-availability') }}">
          @csrf
          @method('PUT')

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="max_mentees">Maximum Active Mentees</label>
              <input type="number" name="max_mentees" id="max_mentees" class="form-control" value="{{ old('max_mentees', $profile->max_mentees) }}" min="1" max="15" required>
              <div class="form-text">Currently guiding: {{ $profile->active_mentees_count }} active mentees.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="mentorship_mode">Mentorship Mode</label>
              <select name="mentorship_mode" id="mentorship_mode" class="form-select" required>
                <option value="online" {{ old('mentorship_mode', $profile->mentorship_mode) === 'online' ? 'selected' : '' }}>Online (Google Meet / Zoom)</option>
                <option value="offline" {{ old('mentorship_mode', $profile->mentorship_mode) === 'offline' ? 'selected' : '' }}>In-Person / Offline</option>
                <option value="both" {{ old('mentorship_mode', $profile->mentorship_mode) === 'both' ? 'selected' : '' }}>Both Online &amp; Offline</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="mentorship_frequency">Preferred Frequency</label>
              <select name="mentorship_frequency" id="mentorship_frequency" class="form-select" required>
                <option value="flexible" {{ old('mentorship_frequency', $profile->mentorship_frequency) === 'flexible' ? 'selected' : '' }}>Flexible</option>
                <option value="one_time" {{ old('mentorship_frequency', $profile->mentorship_frequency) === 'one_time' ? 'selected' : '' }}>One-Time Consultation</option>
                <option value="weekly" {{ old('mentorship_frequency', $profile->mentorship_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="biweekly" {{ old('mentorship_frequency', $profile->mentorship_frequency) === 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                <option value="monthly" {{ old('mentorship_frequency', $profile->mentorship_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold" for="availability_schedule">Available Schedule &amp; Timing</label>
              <input type="text" name="availability_schedule" id="availability_schedule" class="form-control" value="{{ old('availability_schedule', $profile->availability_schedule) }}" placeholder="e.g. Friday 7-9 PM, Weekends by appointment">
            </div>

            <div class="col-12">
              <div class="form-check form-switch p-2 border rounded bg-paper-subtle">
                <input class="form-check-input ms-1" type="checkbox" role="switch" name="is_paused" id="is_paused" value="1" {{ old('is_paused', $profile->is_paused) ? 'checked' : '' }}>
                <label class="form-check-label ps-2 small fw-semibold" for="is_paused">
                  Temporarily Pause New Mentorship Requests
                  <span class="d-block text-muted-slate fw-normal" style="font-size: 11px;">
                    When paused, you will not receive new requests, but your current active mentorships will remain active.
                  </span>
                </label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-brass btn-sm">Save Availability Settings</button>
        </form>
      </div>
    </div>

    {{-- Tab 5: Edit Mentoring Profile --}}
    <div class="tab-pane fade" id="profileEdit" role="tabpanel">
      <div class="card-feature">
        <h3 class="h6 mb-3">Edit Professional Profile &amp; Mentorship Topics</h3>
        <form method="POST" action="{{ route('dashboard.mentorship.update-profile') }}">
          @csrf
          @method('PUT')

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="current_designation">Current Designation <span class="text-danger">*</span></label>
              <input type="text" name="current_designation" id="current_designation" class="form-control" value="{{ old('current_designation', $profile->current_designation) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="company">Company or Organization <span class="text-danger">*</span></label>
              <input type="text" name="company" id="company" class="form-control" value="{{ old('company', $profile->company) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="industry">Industry</label>
              <input type="text" name="industry" id="industry" class="form-control" value="{{ old('industry', $profile->industry) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="country">Country <span class="text-danger">*</span></label>
              <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $profile->country) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="city">City <span class="text-danger">*</span></label>
              <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $profile->city) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="years_of_experience">Years of Experience <span class="text-danger">*</span></label>
              <input type="number" name="years_of_experience" id="years_of_experience" class="form-control" value="{{ old('years_of_experience', $profile->years_of_experience) }}" min="0" max="50" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="linkedin_url">LinkedIn URL</label>
              <input type="url" name="linkedin_url" id="linkedin_url" class="form-control" value="{{ old('linkedin_url', $profile->linkedin_url) }}">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold" for="bio">Professional Bio <span class="text-danger">*</span></label>
              <textarea name="bio" id="bio" rows="3" class="form-control" required>{{ old('bio', $profile->bio) }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold d-block mb-2">Mentorship Categories <span class="text-danger">*</span></label>
              <div class="row g-2">
                @php $mentorCategoryIds = old('categories', $profile->categories->pluck('id')->toArray()); @endphp
                @foreach ($categories as $cat)
                  <div class="col-md-6 col-lg-4">
                    <div class="form-check p-2 border rounded bg-white">
                      <input class="form-check-input ms-1" type="checkbox" name="categories[]" value="{{ $cat->id }}" id="edit_cat_{{ $cat->id }}" {{ in_array($cat->id, $mentorCategoryIds) ? 'checked' : '' }}>
                      <label class="form-check-label ps-2 small fw-semibold" for="edit_cat_{{ $cat->id }}">{{ $cat->name }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="topics_offered">Topics Offered</label>
              <input type="text" name="topics_offered" id="topics_offered" class="form-control" value="{{ old('topics_offered', $profile->topics_offered) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold" for="expertise_areas">Key Skills</label>
              <input type="text" name="expertise_areas" id="expertise_areas" class="form-control" value="{{ old('expertise_areas', $profile->expertise_areas) }}">
            </div>
          </div>

          <button type="submit" class="btn btn-brass btn-sm">Update Profile</button>
        </form>
      </div>
    </div>
  </div>
@endsection
