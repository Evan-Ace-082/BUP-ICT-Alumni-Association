@extends('layouts.app')

@section('title', 'Alumni Directory — BUP ICT Alumni Association')

@section('content')
  {{-- Compact Hero Header --}}
  <section class="hero hero-compact">
    <div class="container">
      <div class="eyebrow mb-2">Alumni Directory</div>
      <h1 class="mb-1">Find fellow graduates</h1>
      <p class="text-muted-glass small mb-0">Search and connect with verified graduates across all cohorts and industries.</p>
    </div>
  </section>

  <section class="section py-4">
    <div class="container">
      {{-- Primary & Collapsible Filter Bar --}}
      @php
        $hasAdvancedFilters = !empty($filters['graduation_year']) || 
                              !empty($filters['blood_group']) || 
                              !empty($filters['company']) || 
                              !empty($filters['country']) || 
                              !empty($filters['city']) || 
                              !empty($filters['skills']) || 
                              !empty($filters['job_status']) || 
                              (isset($filters['mentorship']) && $filters['mentorship'] !== '');
      @endphp

      <div class="card-feature mb-4">
        <form method="GET" action="{{ route('alumni.directory') }}">
          {{-- Primary Controls: Search + Batch + Actions --}}
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label small fw-semibold" for="search">Search</label>
              <div class="input-group">
                <span class="input-group-text border-end-0 text-muted-slate">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Name or Student ID..." value="{{ $filters['search'] ?? '' }}">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-semibold" for="batch_id">Batch</label>
              <select name="batch_id" id="batch_id" class="form-select">
                <option value="">All batches</option>
                @foreach ($batches as $batch)
                  <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? null) == $batch->id)>
                    {{ $batch->ict_name }} ({{ $batch->batch_code }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
              <button type="submit" class="btn btn-brass flex-grow-1">Search</button>
              <button class="btn btn-outline-ink text-nowrap d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}" aria-controls="advancedFilters">
                <span>Filters</span>
                <span class="badge bg-light text-ink border small">{{ $hasAdvancedFilters ? 'Active' : '+' }}</span>
              </button>
              @if(!empty(array_filter($filters ?? [])))
                <a href="{{ route('alumni.directory') }}" class="btn btn-ghost-sm" title="Clear all filters">Reset</a>
              @endif
            </div>
          </div>

          {{-- Collapsible Secondary Filters --}}
          <div class="collapse mt-3 pt-3 border-top {{ $hasAdvancedFilters ? 'show' : '' }}" id="advancedFilters">
            <div class="row g-3">
              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="graduation_year">Graduation Year</label>
                <input type="number" name="graduation_year" id="graduation_year" class="form-control" placeholder="e.g. 2022" value="{{ $filters['graduation_year'] ?? '' }}">
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="blood_group">Blood Group</label>
                <select name="blood_group" id="blood_group" class="form-select">
                  <option value="">All blood groups</option>
                  @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                    <option value="{{ $bg }}" @selected(($filters['blood_group'] ?? null) === $bg)>{{ $bg }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="job_status">Job Status</label>
                <select name="job_status" id="job_status" class="form-select">
                  <option value="">Any status</option>
                  @foreach ($jobStatuses as $status)
                    <option value="{{ $status }}" @selected(($filters['job_status'] ?? null) === $status)>{{ $status }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="mentorship">Mentorship</label>
                <select name="mentorship" id="mentorship" class="form-select">
                  <option value="">Any</option>
                  <option value="1" @selected(($filters['mentorship'] ?? null) === '1')>Willing to mentor</option>
                  <option value="0" @selected(($filters['mentorship'] ?? null) === '0')>Not currently mentoring</option>
                </select>
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="company">Company</label>
                <input type="text" name="company" id="company" class="form-control" placeholder="e.g. Google" value="{{ $filters['company'] ?? '' }}">
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="country">Country</label>
                <input type="text" name="country" id="country" class="form-control" placeholder="e.g. Bangladesh" value="{{ $filters['country'] ?? '' }}">
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="city">City</label>
                <input type="text" name="city" id="city" class="form-control" placeholder="e.g. Dhaka" value="{{ $filters['city'] ?? '' }}">
              </div>

              <div class="col-sm-6 col-md-3">
                <label class="form-label small" for="skills">Skills</label>
                <input type="text" name="skills" id="skills" class="form-control" placeholder="e.g. Python, AI" value="{{ $filters['skills'] ?? '' }}">
              </div>
            </div>
          </div>
        </form>
      </div>

      {{-- Results Count Bar --}}
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted-slate">
          Showing <strong>{{ $alumni->total() }}</strong> verified {{ \Illuminate\Support\Str::plural('graduate', $alumni->total()) }}
        </div>
      </div>

      {{-- Results Grid --}}
      @if ($alumni->isEmpty())
        <div class="placeholder-panel py-5 text-center">
          <div class="empty-state-icon mx-auto mb-3">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </div>
          <h2 class="h6 mb-2 fw-semibold">No alumni match these filters</h2>
          <p class="small text-muted-slate mb-3" style="max-width: 24rem; margin: auto;">We couldn't find any graduates matching your search criteria. Try broadening your keywords or clearing active filters.</p>
          <a href="{{ route('alumni.directory') }}" class="btn btn-outline-ink btn-sm">Clear all filters</a>
        </div>
      @else
        <div class="row g-3">
          @foreach ($alumni as $record)
            <div class="col-6 col-md-4 col-lg-3">
              <a href="{{ route('alumni.show', $record) }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card-feature h-100 d-flex flex-column justify-content-between p-3 position-relative">
                  <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      {{-- Avatar initials --}}
                      <div class="avatar-initials">
                        {{ strtoupper(substr($record->full_name, 0, 2)) }}
                      </div>
                      <span class="badge bg-parchment text-brass border small font-mono">
                        {{ $record->batch?->ict_name ?? $record->batch?->batch_code ?? 'BUP' }}
                      </span>
                    </div>

                    <h2 class="h6 mb-1 text-ink fw-bold">{{ $record->full_name }}</h2>
                    <p class="small text-muted-slate mb-1 font-mono">{{ $record->student_id }}</p>

                    @if ($record->company)
                      <p class="small text-muted-slate mb-0 d-flex align-items-center gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        <span class="text-truncate">{{ $record->company }}</span>
                      </p>
                    @endif
                  </div>

                  <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="small text-brass fw-semibold">View Profile</span>
                    <span class="small text-brass">&rarr;</span>
                  </div>
                </div>
              </a>
            </div>
          @endforeach
        </div>

        <div class="mt-4">{{ $alumni->links() }}</div>
      @endif
    </div>
  </section>
@endsection
