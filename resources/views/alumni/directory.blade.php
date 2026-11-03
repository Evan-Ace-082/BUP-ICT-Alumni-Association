@extends('layouts.app')

@section('title', 'Alumni Directory — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">Alumni Directory</div>
      <h1 class="mb-0">Find fellow graduates</h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="card-feature mb-4">
        <form method="GET" action="{{ route('alumni.directory') }}" class="row g-3">
          <div class="col-md-4">
            <label class="form-label small" for="search">Name or Student ID</label>
            <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="batch_id">Batch</label>
            <select name="batch_id" id="batch_id" class="form-select">
              <option value="">All batches</option>
              @foreach ($batches as $batch)
                <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? null) == $batch->id)>
                  {{ $batch->ict_name }} ({{ $batch->batch_code }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="graduation_year">Graduation Year</label>
            <input type="number" name="graduation_year" id="graduation_year" class="form-control" value="{{ $filters['graduation_year'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="blood_group">Blood Group</label>
            <input type="text" name="blood_group" id="blood_group" class="form-control" value="{{ $filters['blood_group'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="company">Company</label>
            <input type="text" name="company" id="company" class="form-control" value="{{ $filters['company'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="country">Country</label>
            <input type="text" name="country" id="country" class="form-control" value="{{ $filters['country'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="city">City</label>
            <input type="text" name="city" id="city" class="form-control" value="{{ $filters['city'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="skills">Skills</label>
            <input type="text" name="skills" id="skills" class="form-control" value="{{ $filters['skills'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="job_status">Job Status</label>
            <select name="job_status" id="job_status" class="form-select">
              <option value="">Any</option>
              @foreach ($jobStatuses as $status)
                <option value="{{ $status }}" @selected(($filters['job_status'] ?? null) === $status)>{{ $status }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small" for="mentorship">Mentorship</label>
            <select name="mentorship" id="mentorship" class="form-select">
              <option value="">Any</option>
              <option value="1" @selected(($filters['mentorship'] ?? null) === '1')>Willing to mentor</option>
              <option value="0" @selected(($filters['mentorship'] ?? null) === '0')>Not currently mentoring</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brass">Search</button>
            <a href="{{ route('alumni.directory') }}" class="btn btn-outline-ink">Reset filters</a>
          </div>
        </form>
      </div>

      @if ($alumni->isEmpty())
        <div class="placeholder-panel">
          <h2 class="h6 mb-2">No alumni match these filters</h2>
          <p class="mb-0">Try a broader search or clear the filters above.</p>
        </div>
      @else
        <div class="row g-3">
          @foreach ($alumni as $record)
            <div class="col-6 col-md-4 col-lg-3">
              <a href="{{ route('alumni.show', $record) }}" class="text-decoration-none text-reset">
                <div class="card-feature h-100">
                  <div class="icon-badge">{{ $record->batch?->ict_name ?? $record->batch?->batch_code ?? '—' }}</div>
                  <h2 class="h6 mb-1">{{ $record->full_name }}</h2>
                  <p class="small text-muted-slate mb-0">{{ $record->student_id }}</p>
                  @if ($record->company)
                    <p class="small text-muted-slate mb-0">{{ $record->company }}</p>
                  @endif
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
