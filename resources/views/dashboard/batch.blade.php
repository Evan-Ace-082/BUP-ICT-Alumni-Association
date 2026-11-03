@extends('layouts.dashboard')

@section('title', 'My Batch — BUP ICT Alumni Association')
@section('page_title', 'My Batch')


@section('content')
  @if ($batch)
    <div class="card-feature mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-brass text-dark fw-bold fs-6">{{ $batch->batch_code }}</span>
            <h2 class="h5 mb-0 font-display text-ink">{{ $batch->batch_name }}</h2>
          </div>
          <p class="small text-muted-slate mb-0">
            Session: <strong>{{ $batch->session ?? 'Not provided' }}</strong>
            @if ($batch->graduation_year)
              | Graduation: <strong>{{ $batch->graduation_year }}</strong>
            @endif
            @if ($batchmates)
              | Members: <strong>{{ $batchmates->total() }}</strong>
            @endif
          </p>
          @if ($batch->description)
            <p class="small text-muted-slate mb-0 mt-2">{{ $batch->description }}</p>
          @endif
        </div>
      </div>
    </div>

    <!-- Search Form -->
    <div class="card-feature mb-4">
      <form method="GET" action="{{ route('dashboard.batch') }}" class="row g-2 align-items-end">
        <div class="col-md-9 col-sm-8">
          <label class="form-label small fw-semibold text-ink" for="search">Find Batchmates</label>
          <input type="text" name="search" id="search" class="form-control form-control-sm"
                 placeholder="Search by name, student ID, company, or designation..." value="{{ $search }}">
        </div>
        <div class="col-md-3 col-sm-4 d-flex gap-2">
          <button type="submit" class="btn btn-brass btn-sm flex-grow-1">Search</button>
          @if ($search)
            <a href="{{ route('dashboard.batch') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
          @endif
        </div>
      </form>
    </div>

    <!-- Batchmates Directory Grid -->
    @if ($batchmates && $batchmates->isNotEmpty())
      <div class="row g-3">
        @foreach ($batchmates as $member)
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card-feature h-100 d-flex flex-column p-3 border rounded bg-white shadow-sm">
              <!-- Top Row: Photo & Basic Details -->
              <div class="d-flex align-items-center gap-3 mb-3">
                @if ($member->profile_photo_url)
                  <a href="{{ route('alumni.show', $member) }}" class="text-decoration-none flex-shrink-0">
                    <img src="{{ $member->profile_photo_url }}" alt="{{ $member->full_name }}" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                  </a>
                @else
                  <a href="{{ route('alumni.show', $member) }}" class="text-decoration-none flex-shrink-0">
                    <div class="rounded-circle bg-brass d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 50px; height: 50px; font-size: 1.2rem;">
                      {{ substr($member->full_name, 0, 1) }}
                    </div>
                  </a>
                @endif
                <div class="overflow-hidden">
                  <h3 class="h6 mb-0 fw-bold">
                    <a href="{{ route('alumni.show', $member) }}" class="text-decoration-none text-ink">
                      {{ $member->full_name }}
                    </a>
                  </h3>
                  <div class="small text-muted-slate">ID: {{ $member->student_id }}</div>
                  @if ($member->job_status)
                    <span class="badge bg-light text-dark border small mt-1" style="font-size: 0.72rem;">{{ $member->job_status }}</span>
                  @endif
                </div>
              </div>

              <!-- Body: Information fields -->
              <div class="small text-muted-slate flex-grow-1">
                <div class="mb-1">
                  <span class="fw-semibold text-dark">Session:</span> {{ $member->session ?: ($batch->session ?: 'Not provided') }}
                </div>

                @if ($member->designation || $member->company || $member->current_institution)
                  <div class="mb-1">
                    <span class="fw-semibold text-dark">Role:</span>
                    {{ $member->designation ?: 'Position not specified' }}
                    @if ($member->company || $member->current_institution)
                      at <span class="text-dark">{{ $member->company ?: $member->current_institution }}</span>
                    @endif
                  </div>
                @else
                  <div class="mb-1">
                    <span class="fw-semibold text-dark">Role:</span> Not provided
                  </div>
                @endif

                @if ($member->higher_studies)
                  <div class="mb-1">
                    <span class="fw-semibold text-dark">Higher Studies:</span> {{ $member->higher_studies }}
                  </div>
                @endif

                @if ($member->city || $member->country)
                  <div class="mb-1">
                    <span class="fw-semibold text-dark">Location:</span>
                    {{ collect([$member->city, $member->country])->filter()->join(', ') }}
                  </div>
                @endif

                @if ($member->skills)
                  <div class="mt-2">
                    <span class="fw-semibold text-dark d-block mb-1">Skills:</span>
                    <div class="d-flex flex-wrap gap-1">
                      @foreach (array_slice(array_map('trim', explode(',', $member->skills)), 0, 5) as $skill)
                        @if ($skill)
                          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">{{ $skill }}</span>
                        @endif
                      @endforeach
                    </div>
                  </div>
                @endif
              </div>

              <!-- Card Footer -->
              <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                <a href="{{ route('alumni.show', $member) }}" class="btn btn-outline-ink btn-sm py-1 px-2" style="font-size: 0.8rem;">
                  View Profile &rarr;
                </a>
                @if ($member->mentorship)
                  <span class="badge bg-success-subtle text-success small border border-success-subtle" title="Available for mentorship">
                    Mentor
                  </span>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-4">
        {{ $batchmates->links() }}
      </div>
    @elseif ($search)
      <div class="placeholder-panel text-center py-4">
        <h3 class="h6 mb-2">No batchmates match "{{ $search }}"</h3>
        <p class="small text-muted-slate mb-3">Try searching with a different name, student ID, company, or designation.</p>
        <a href="{{ route('dashboard.batch') }}" class="btn btn-outline-ink btn-sm">Clear Search</a>
      </div>
    @else
      <div class="placeholder-panel text-center py-4">
        <h3 class="h6 mb-2">No alumni registered in this batch yet</h3>
        <p class="small text-muted-slate mb-0">Alumni records will appear here as soon as batch members register or are imported.</p>
      </div>
    @endif
  @elseif ($alumni)
    <div class="placeholder-panel">
      <h2 class="h5 mb-2">No batch assigned</h2>
      <p class="mb-0">
        Your alumni record isn't currently linked to a batch. Contact an administrator if
        you believe this is incorrect.
      </p>
    </div>
  @else
    <div class="placeholder-panel">
      <h2 class="h5 mb-2">No alumni record linked</h2>
      <p class="mb-0">
        Your account isn't currently linked to an official alumni record, so no batch
        information is available yet.
      </p>
    </div>
  @endif
@endsection
