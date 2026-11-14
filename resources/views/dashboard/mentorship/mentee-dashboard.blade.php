@extends('layouts.dashboard')
@section('title', 'My Mentorships — BUP ICT Alumni Association')
@section('page_title', 'My Mentorships')

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

  {{-- Header & Direct Actions --}}
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h5 mb-1">Mentee Hub &amp; Requests</h1>
      <p class="small text-muted-slate mb-0">Track your mentorship connections, pending requests, and scheduled sessions.</p>
    </div>
    <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-brass btn-sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Browse All Mentors
    </a>
  </div>

  {{-- Stats Summary --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Active Mentorships</div>
        <div class="h4 mb-0 text-success">{{ $activeMentorships->count() }}</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Pending Requests</div>
        <div class="h4 mb-0 text-brass">{{ $pendingRequests->count() }}</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-feature text-center p-3 h-100">
        <div class="small text-muted-slate mb-1">Completed Engagements</div>
        <div class="h4 mb-0 text-ink-900">{{ $completedMentorships->count() }}</div>
      </div>
    </div>
  </div>

  {{-- Section 1: Active Mentorships --}}
  <div class="card-feature mb-4">
    <h2 class="h6 mb-3 d-flex align-items-center gap-2">
      <span class="badge bg-success" style="width: 10px; height: 10px; border-radius: 50%; padding: 0;"></span>
      Active Mentorships ({{ $activeMentorships->count() }})
    </h2>

    @if ($activeMentorships->isEmpty())
      <div class="text-center py-4 bg-paper-subtle rounded border">
        <p class="small text-muted-slate mb-2">You currently have no active mentorship engagements.</p>
        <a href="{{ route('dashboard.mentorship.index') }}" class="btn btn-outline-ink btn-sm">Find and Connect with a Mentor &rarr;</a>
      </div>
    @else
      <div class="row g-3">
        @foreach ($activeMentorships as $req)
          @php
            $mentorUser = $req->mentor;
            $mAlumni = $mentorUser->alumni;
            $mentorName = $mAlumni?->full_name ?? $mentorUser->name;
            $mentorProfile = $req->mentorProfile ?? $mentorUser->mentorProfile;
          @endphp
          <div class="col-md-6">
            <div class="card-feature border h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <h3 class="h6 mb-0">{{ $mentorName }}</h3>
                    <div class="small text-ink-900 fw-semibold">{{ $mentorProfile?->current_designation ?? ($mAlumni?->job_title ?? 'Mentor') }}</div>
                    <div class="small text-muted-slate">{{ $mentorProfile?->company ?? ($mAlumni?->company ?? 'BUP ICT') }}</div>
                  </div>
                  <span class="badge bg-success-subtle text-success">Active</span>
                </div>

                <div class="p-2 rounded bg-paper-subtle mb-3 small">
                  <div class="d-flex justify-content-between py-1">
                    <span class="text-muted-slate">Mentor Email:</span>
                    <a href="mailto:{{ $mentorUser->email }}" class="fw-semibold">{{ $mentorUser->email }}</a>
                  </div>
                  <div class="d-flex justify-content-between py-1">
                    <span class="text-muted-slate">Category:</span>
                    <span class="fw-semibold">{{ $req->category?->name ?? 'General Guidance' }}</span>
                  </div>
                  <div class="d-flex justify-content-between py-1">
                    <span class="text-muted-slate">Connected:</span>
                    <span>{{ $req->responded_at?->format('M d, Y') ?? 'Active' }}</span>
                  </div>
                </div>

                @if ($req->specific_topic)
                  <p class="small text-muted-slate mb-2"><strong>Topic:</strong> {{ $req->specific_topic }}</p>
                @endif
              </div>

              <div class="pt-3 border-top d-flex gap-2">
                <a href="mailto:{{ $mentorUser->email }}" class="btn btn-outline-ink btn-sm flex-fill">Contact Mentor</a>
                <button type="button" class="btn btn-brass btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#menteeCompleteModal{{ $req->id }}">
                  Mark Completed
                </button>
              </div>
            </div>
          </div>

          {{-- Mentee Complete Modal --}}
          <div class="modal fade" id="menteeCompleteModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route('dashboard.mentorship.requests.complete', $req) }}">
                  @csrf
                  @method('PATCH')
                  <div class="modal-header">
                    <h5 class="modal-title h6">Complete Mentorship with {{ $mentorName }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p class="small text-muted-slate mb-3">
                      Have you concluded your mentorship sessions? Marking this completed will help keep your active mentorship dashboard current.
                    </p>
                    <label class="form-label small fw-semibold" for="mentee_feedback_{{ $req->id }}">Closing Feedback / Appreciation (Optional)</label>
                    <textarea name="feedback" id="mentee_feedback_{{ $req->id }}" rows="3" class="form-control form-control-sm" placeholder="Share what you learned or leave a note of appreciation..."></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brass btn-sm">Mark Completed</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Section 2: Pending Requests --}}
  <div class="card-feature mb-4">
    <h2 class="h6 mb-3">Pending Mentorship Requests ({{ $pendingRequests->count() }})</h2>

    @if ($pendingRequests->isEmpty())
      <p class="small text-muted-slate mb-0">No pending mentorship requests sent.</p>
    @else
      <div class="d-flex flex-column gap-3">
        @foreach ($pendingRequests as $pReq)
          @php
            $pMentorUser = $pReq->mentor;
            $pName = $pMentorUser->alumni?->full_name ?? $pMentorUser->name;
          @endphp
          <div class="card-feature border p-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div>
                <h3 class="h6 mb-0">{{ $pName }}</h3>
                <div class="small text-muted-slate">Requested {{ $pReq->created_at->diffForHumans() }} &middot; Category: <strong>{{ $pReq->category?->name ?? 'General' }}</strong></div>
              </div>
              <span class="badge bg-warning text-dark">Pending Mentor Review</span>
            </div>

            @if ($pReq->specific_topic)
              <div class="small text-muted-slate mb-2"><strong>Question/Topic:</strong> {{ $pReq->specific_topic }}</div>
            @endif

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="small text-muted-slate">Preferred Mode: {{ ucfirst($pReq->preferred_mode) }}</span>
              <form method="POST" action="{{ route('dashboard.mentorship.requests.cancel', $pReq) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this mentorship request?')">
                  Cancel Request
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Section 3: Completed Mentorships History --}}
  @if ($completedMentorships->isNotEmpty())
    <div class="card-feature mb-4">
      <h2 class="h6 mb-3">Completed Engagements ({{ $completedMentorships->count() }})</h2>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted-slate">
              <th>Mentor</th>
              <th>Category</th>
              <th>Topic</th>
              <th>Completed Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            @foreach ($completedMentorships as $comp)
              <tr>
                <td class="fw-semibold">{{ $comp->mentor->alumni?->full_name ?? $comp->mentor->name }}</td>
                <td>{{ $comp->category?->name ?? 'General' }}</td>
                <td>{{ $comp->specific_topic ?: '—' }}</td>
                <td>{{ $comp->completed_at?->format('M d, Y') ?? 'Completed' }}</td>
                <td><span class="badge bg-light border text-muted-slate">Completed</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- Section 4: History of Declined/Cancelled --}}
  @if ($declinedRequests->isNotEmpty())
    <div class="card-feature">
      <h2 class="h6 mb-3">Request History (Declined / Cancelled)</h2>
      <div class="d-flex flex-column gap-2">
        @foreach ($declinedRequests as $dReq)
          <div class="p-2 border rounded small d-flex justify-content-between align-items-center">
            <div>
              <span class="fw-semibold">{{ $dReq->mentor->alumni?->full_name ?? $dReq->mentor->name }}</span>
              <span class="text-muted-slate"> &middot; {{ $dReq->category?->name ?? 'General' }}</span>
              @if ($dReq->decline_reason)
                <div class="text-muted-slate" style="font-size: 11px;">Note: "{{ $dReq->decline_reason }}"</div>
              @endif
            </div>
            <span class="badge bg-secondary">{{ ucfirst($dReq->status) }}</span>
          </div>
        @endforeach
      </div>
    </div>
  @endif
@endsection
