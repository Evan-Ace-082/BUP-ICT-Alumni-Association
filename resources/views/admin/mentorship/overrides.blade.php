@extends('layouts.dashboard')
@section('title', 'Eligibility Overrides — Admin Panel')
@section('page_title', 'User-Level Mentorship Overrides')

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

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">Exceptional Eligibility Overrides</h1>
      <p class="small text-muted-slate mb-0">Manually override individual user eligibility for exceptional cohort or academic cases.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
  </div>

  <div class="row g-4 mb-4">
    {{-- Search and Create Override Card --}}
    <div class="col-lg-5">
      <div class="card-feature mb-4">
        <h2 class="h6 mb-3">1. Find User to Override</h2>
        <form method="GET" action="{{ route('admin.mentorship.overrides') }}" class="mb-3">
          <div class="input-group input-group-sm">
            <input type="text" name="search_user" class="form-control" placeholder="Search by user name or email..." value="{{ $searchUser ?? '' }}" required>
            <button class="btn btn-brass" type="submit">Search</button>
          </div>
        </form>

        @if (isset($searchUser) && $userSearchResults->isEmpty())
          <div class="alert alert-warning small mb-0">No users found matching "{{ $searchUser }}".</div>
        @elseif ($userSearchResults->isNotEmpty())
          <div class="border rounded p-2 bg-paper-subtle mb-3">
            <div class="small fw-semibold text-muted-slate mb-2">Search Results:</div>
            <div class="d-flex flex-column gap-2" style="max-height: 200px; overflow-y: auto;">
              @foreach ($userSearchResults as $u)
                <div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold small text-ink-900">{{ $u->name }}</div>
                    <div class="text-muted-slate" style="font-size: 11px;">{{ $u->email }}</div>
                    <div class="text-muted-slate" style="font-size: 10px;">
                      {{ $u->student ? 'Student' : ($u->alumni?->batch?->batch_code ?? 'User') }} &middot; Grad: {{ $u->alumni?->graduation_year ?? 'N/A' }}
                    </div>
                  </div>
                  <button type="button" class="btn btn-outline-ink btn-sm py-0 px-2" style="font-size: 11px;" onclick="selectUserForOverride({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->email) }}')">
                    Select
                  </button>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        {{-- Override Form --}}
        <h2 class="h6 mb-3">2. Set Eligibility Override</h2>
        <form method="POST" action="{{ route('admin.mentorship.overrides.store') }}" id="overrideForm">
          @csrf
          <input type="hidden" name="user_id" id="selected_user_id" value="{{ old('user_id') }}">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Target User</label>
            <div id="selected_user_display" class="p-2 border rounded bg-light small text-muted-slate">
              Please search and select a user above.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold" for="override_type">Override Eligibility Rule <span class="text-danger">*</span></label>
            <select name="override_type" id="override_type" class="form-select form-select-sm" required>
              <option value="auto">Auto Detect (Default Cohort Rules)</option>
              <option value="force_mentee" {{ old('override_type') === 'force_mentee' ? 'selected' : '' }}>Force Eligible as Mentee</option>
              <option value="force_mentor" {{ old('override_type') === 'force_mentor' ? 'selected' : '' }}>Force Eligible as Mentor</option>
              <option value="ineligible" {{ old('override_type') === 'ineligible' ? 'selected' : '' }}>Ineligible (Deny Mentorship Access)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold" for="override_reason">Audited Reason <span class="text-danger">*</span></label>
            <textarea name="reason" id="override_reason" rows="2" class="form-control form-control-sm" placeholder="Explain the justification for this exceptional override..." required>{{ old('reason') }}</textarea>
            <div class="form-text">Recorded with your admin identity for compliance auditing.</div>
          </div>

          <button type="submit" class="btn btn-brass btn-sm w-100" id="saveOverrideBtn" disabled>
            Save User Override
          </button>
        </form>
      </div>
    </div>

    {{-- Active Overrides Table --}}
    <div class="col-lg-7">
      <div class="card-feature p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0">Active Eligibility Overrides ({{ $overrides->total() }})</h2>
        </div>

        @if ($overrides->isEmpty())
          <div class="text-center py-5">
            <p class="small text-muted-slate mb-0">No custom overrides configured. All users are governed by automatic cohort rules.</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr class="text-muted-slate">
                  <th>User</th>
                  <th>Override Status</th>
                  <th>Justification / Reason</th>
                  <th>Authorized By</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($overrides as $ov)
                  <tr>
                    <td>
                      <div class="fw-semibold text-ink-900">{{ $ov->user->name }}</div>
                      <div class="text-muted-slate" style="font-size: 11px;">{{ $ov->user->email }}</div>
                    </td>
                    <td>
                      @if ($ov->override_type === 'force_mentee')
                        <span class="badge bg-success-subtle text-success">Forced Mentee</span>
                      @elseif ($ov->override_type === 'force_mentor')
                        <span class="badge bg-brass-subtle text-brass">Forced Mentor</span>
                      @elseif ($ov->override_type === 'ineligible')
                        <span class="badge bg-danger-subtle text-danger">Ineligible</span>
                      @else
                        <span class="badge bg-light text-muted-slate">Auto</span>
                      @endif
                    </td>
                    <td>
                      <div class="text-muted-slate" style="max-width: 220px;">{{ $ov->reason }}</div>
                    </td>
                    <td>
                      <div class="text-ink-900">{{ $ov->admin?->name ?? 'Admin' }}</div>
                      <div class="text-muted-slate" style="font-size: 10px;">{{ $ov->updated_at->format('M d, Y') }}</div>
                    </td>
                    <td class="text-end">
                      <form method="POST" action="{{ route('admin.mentorship.overrides.destroy', $ov) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 11px;" onclick="return confirm('Remove override and return user to automatic cohort calculation?')">
                          Reset
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="p-3 border-top">
            {{ $overrides->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>

  <script>
    function selectUserForOverride(id, name, email) {
      document.getElementById('selected_user_id').value = id;
      document.getElementById('selected_user_display').innerHTML = '<strong>' + name + '</strong> (' + email + ')';
      document.getElementById('saveOverrideBtn').disabled = false;
    }
  </script>
@endsection
