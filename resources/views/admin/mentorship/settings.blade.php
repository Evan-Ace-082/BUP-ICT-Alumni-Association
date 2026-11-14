@extends('layouts.dashboard')
@section('title', 'Cohort & Eligibility Settings — Admin Panel')
@section('page_title', 'Cohort Detection & Eligibility Settings')

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
      <h1 class="h5 mb-0">Cohort Detection &amp; Eligibility Configuration</h1>
      <p class="small text-muted-slate mb-0">Manage how the system identifies the newest graduating cohort and seniors eligible to mentor.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
  </div>

  <div class="row g-4">
    {{-- Settings Form --}}
    <div class="col-lg-7">
      <div class="card-feature mb-4">
        <h2 class="h6 mb-3">System Configuration Settings</h2>
        <form method="POST" action="{{ route('admin.mentorship.settings.update') }}">
          @csrf
          @method('PUT')

          {{-- Cohort Mode Selection (Section 12) --}}
          <div class="mb-4">
            <label class="form-label small fw-semibold d-block mb-2">Cohort Detection Mode <span class="text-danger">*</span></label>
            <div class="row g-2">
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-white">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="cohort_mode" id="mode_manual" value="manual" {{ old('cohort_mode', $settings->cohort_mode) === 'manual' ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="mode_manual">
                      Manual Cohort Selection (Recommended)
                    </label>
                  </div>
                  <p class="small text-muted-slate mt-2 mb-0" style="font-size: 11px;">
                    Super Admin explicitly configures the latest completed graduation year. Safely avoids incomplete data in the batches database.
                  </p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-white">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="cohort_mode" id="mode_automatic" value="automatic" {{ old('cohort_mode', $settings->cohort_mode) === 'automatic' ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="mode_automatic">
                      Automatic Cohort Detection
                    </label>
                  </div>
                  <p class="small text-muted-slate mt-2 mb-0" style="font-size: 11px;">
                    Dynamically determines the newest graduating cohort using verified alumni and completed batch records (excluding future years).
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- Latest Completed Graduation Year (Section 2 & 12) --}}
          <div class="mb-3">
            <label class="form-label small fw-semibold" for="latest_graduation_year">
              Latest Completed Graduation Year (Manual Setting) <span class="text-danger">*</span>
            </label>
            <input type="number" name="latest_graduation_year" id="latest_graduation_year" class="form-control" value="{{ old('latest_graduation_year', $settings->latest_graduation_year) }}" min="2015" max="{{ date('Y') }}" required>
            <div class="form-text">
              Alumni from this exact graduation year are classified as the <strong>Newest Graduating Cohort</strong> (eligible as Mentees). Verified alumni graduating in earlier years are eligible to become <strong>Mentors</strong>.
            </div>
          </div>

          {{-- Max Pending Requests per Mentee --}}
          <div class="mb-3">
            <label class="form-label small fw-semibold" for="max_active_requests_per_mentee">
              Maximum Concurrent Pending Requests per Mentee
            </label>
            <input type="number" name="max_active_requests_per_mentee" id="max_active_requests_per_mentee" class="form-control" value="{{ old('max_active_requests_per_mentee', $settings->max_active_requests_per_mentee) }}" min="1" max="10" required>
            <div class="form-text">Prevents spamming multiple mentors simultaneously.</div>
          </div>

          {{-- Global Mentorship Toggle --}}
          <div class="mb-4">
            <div class="form-check form-switch p-2 border rounded bg-paper-subtle">
              <input class="form-check-input ms-1" type="checkbox" role="switch" name="mentorship_enabled" id="mentorship_enabled" value="1" {{ old('mentorship_enabled', $settings->mentorship_enabled) ? 'checked' : '' }}>
              <label class="form-check-label ps-2 small fw-semibold" for="mentorship_enabled">
                Mentorship System Is Globally Active
                <span class="d-block text-muted-slate fw-normal" style="font-size: 11px;">
                  Uncheck to temporarily pause new mentorship requests across the entire application (e.g. during examination or semester break periods).
                </span>
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-brass">Save Settings</button>
        </form>
      </div>
    </div>

    {{-- Data Completeness Audit (Section 2 & 12) --}}
    <div class="col-lg-5">
      <div class="card-feature mb-4 border-brass">
        <h2 class="h6 mb-2">Cohort Database Audit Report</h2>
        <p class="small text-muted-slate mb-3">Assessment of graduation years currently recorded in the database.</p>

        <div class="border rounded p-3 mb-3 bg-white small">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted-slate">Total Batches:</span>
            <span class="fw-semibold">{{ $auditData['total_batches'] }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted-slate">Batches with Graduation Year:</span>
            <span class="fw-semibold text-success">{{ $auditData['batches_with_year'] }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted-slate">Batches Missing Year (NULL):</span>
            <span class="fw-semibold {{ $auditData['batches_without_year'] > 0 ? 'text-danger' : 'text-success' }}">
              {{ $auditData['batches_without_year'] }}
            </span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted-slate">Total Verified Alumni:</span>
            <span class="fw-semibold">{{ $auditData['verified_alumni'] }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted-slate">Verified Alumni with Year:</span>
            <span class="fw-semibold">{{ $auditData['verified_alumni_with_year'] }}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted-slate">Effective Latest Cohort:</span>
            <span class="badge bg-brass text-white">Class of {{ $auditData['effective_latest_year'] }}</span>
          </div>
        </div>

        @if ($auditData['batches_without_year'] > 0)
          <div class="alert alert-warning small mb-0">
            <strong>Data Notice:</strong> {{ $auditData['batches_without_year'] }} batches have not yet been assigned a graduation year in the batches management panel.
            Keep <strong>Manual Cohort Selection</strong> active until batch records are completed.
          </div>
        @else
          <div class="alert alert-success small mb-0">
            <strong>Database Ready:</strong> All batch records contain valid graduation years. Automatic detection is fully operational.
          </div>
        @endif
      </div>

      {{-- Batch Reference Table --}}
      <div class="card-feature p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h3 class="h6 mb-0">Registered Cohort Batches</h3>
          <a href="{{ route('admin.batches.index') }}" class="btn btn-ghost-sm btn-sm">Edit Batches &rarr;</a>
        </div>
        <div class="table-responsive" style="max-height: 260px;">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light sticky-top">
              <tr>
                <th>Code</th>
                <th>Batch Name</th>
                <th>Graduation Year</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($batches as $b)
                <tr>
                  <td class="fw-semibold">{{ $b->batch_code }}</td>
                  <td>{{ $b->batch_name }}</td>
                  <td>
                    @if ($b->graduation_year)
                      <span class="badge bg-light border text-ink-900">{{ $b->graduation_year }}</span>
                    @else
                      <span class="text-danger" style="font-size: 11px;">Not set</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
