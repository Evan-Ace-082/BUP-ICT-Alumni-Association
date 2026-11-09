@extends('layouts.app')

@section('title', $batch->ict_name . ' — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <a href="{{ route('batches.index') }}" class="small d-inline-block mb-3 text-brass text-decoration-none">
        &larr; Back to Batches
      </a>
      <div class="eyebrow mb-2">Batch Directory</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <h1 class="mb-0 font-display">{{ $batch->ict_name }}</h1>
        <span class="badge bg-brass text-white">{{ $batch->batch_code }}</span>
      </div>
      <div class="d-flex flex-wrap gap-3 small text-muted-slate mt-2">
        <span><strong>{{ $alumniCount }}</strong> {{ \Illuminate\Support\Str::plural('Alumnus', $alumniCount) }}</span>
        @if ($batch->session)
          <span>&bull; Session: <strong>{{ $batch->session }}</strong></span>
        @endif
        @if ($batch->graduation_year)
          <span>&bull; Graduation Year: <strong>{{ $batch->graduation_year }}</strong></span>
        @endif
      </div>
      @if ($batch->description)
        <p class="text-muted-slate mt-2 mb-0">{{ $batch->description }}</p>
      @endif
    </div>
  </section>

  <section class="section py-4 section-highlight">
    <div class="container">
      @php
        $batchPhoto = 'images/batches/' . strtolower($batch->batch_code) . '.jpg';
      @endphp

      @if (file_exists(public_path($batchPhoto)))
        <div class="mb-4">
          <div class="card-feature p-0 overflow-hidden" style="max-height: 460px;">
            <img src="{{ asset($batchPhoto) }}" alt="{{ $batch->ict_name }} Official Photo" class="w-100" style="object-fit: cover; max-height: 460px; display: block;">
          </div>
        </div>
      @endif
      @if ($alumni->isEmpty())
        <div class="placeholder-panel text-center py-5">
          <div class="icon-badge mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.25rem;">
            {{ $batch->ict_name }}
          </div>
          <h2 class="h5 mb-2 font-display">No alumni registered yet</h2>
          <p class="text-muted-slate mb-4">No alumni records are currently listed under {{ $batch->ict_name }}.</p>
          <a href="{{ route('batches.index') }}" class="btn btn-brass btn-sm">
            &larr; Back to Batches
          </a>
        </div>
      @else
        <div class="row g-3">
          @foreach ($alumni as $member)
            <div class="col-12 col-md-6 col-lg-4">
              <div class="card-feature h-100 d-flex flex-column justify-content-between p-3">
                <div>
                  <div class="d-flex align-items-center gap-3 mb-3">
                    @if ($member->profile_photo_url)
                      <a href="{{ route('alumni.show', $member) }}" class="flex-shrink-0 text-decoration-none">
                        <img src="{{ $member->profile_photo_url }}" alt="{{ $member->full_name }}" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                      </a>
                    @else
                      <a href="{{ route('alumni.show', $member) }}" class="flex-shrink-0 text-decoration-none">
                        <div class="rounded-circle bg-brass d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 50px; height: 50px; font-size: 1.2rem;">
                          {{ substr($member->full_name, 0, 1) }}
                        </div>
                      </a>
                    @endif
                    <div class="overflow-hidden">
                      <h2 class="h6 mb-0 fw-bold">
                        <a href="{{ route('alumni.show', $member) }}" class="text-decoration-none text-ink">
                          {{ $member->full_name }}
                        </a>
                      </h2>
                      <div class="small text-muted-slate">ID: {{ $member->student_id }}</div>
                      @if ($member->job_status)
                        <span class="badge bg-light text-dark border small mt-1" style="font-size: 0.72rem;">{{ $member->job_status }}</span>
                      @endif
                    </div>
                  </div>

                  <div class="small text-muted-slate">
                    @if ($member->session || $batch->session)
                      <div class="mb-1">
                        <span class="fw-semibold text-dark">Session:</span> {{ $member->session ?: ($batch->session ?: 'Not provided') }}
                      </div>
                    @endif
                    @if ($member->graduation_year || $batch->graduation_year)
                      <div class="mb-1">
                        <span class="fw-semibold text-dark">Graduation:</span> {{ $member->graduation_year ?: ($batch->graduation_year ?: '—') }}
                      </div>
                    @endif
                    @if ($member->designation || $member->company || $member->current_institution)
                      <div class="mb-1">
                        <span class="fw-semibold text-dark">Current Role:</span>
                        {{ $member->designation ?: 'Alumnus' }}
                        @if ($member->company || $member->current_institution)
                          at <span class="text-dark fw-semibold">{{ $member->company ?: $member->current_institution }}</span>
                        @endif
                      </div>
                    @endif
                    @if ($member->city || $member->country)
                      <div class="mb-1">
                        <span class="fw-semibold text-dark">Location:</span>
                        {{ collect([$member->city, $member->country])->filter()->join(', ') }}
                      </div>
                    @endif
                  </div>
                </div>

                <div class="mt-3 pt-2 border-top">
                  <a href="{{ route('alumni.show', $member) }}" class="btn btn-outline-ink btn-sm w-100">
                    View Profile &rarr;
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-4">
          <a href="{{ route('batches.index') }}" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Batches
          </a>
        </div>
      @endif
    </div>
  </section>
@endsection
