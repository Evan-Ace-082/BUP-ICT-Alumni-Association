@extends('layouts.app')

@section('title', ($profile['full_name'] ?? 'Alumni').' — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">Alumni Profile</div>
      <h1 class="mb-0">{{ $profile['full_name'] }}</h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <a href="{{ route('alumni.directory') }}" class="small d-inline-block mb-3">&larr; Back to Alumni Directory</a>

      <div class="card-feature">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted-slate">Student ID</div>
            <div>{{ $profile['student_id'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Batch</div>
            <div>{{ $profile['batch_name'] ?? '—' }} ({{ $profile['batch_code'] ?? '—' }})</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Graduation Year</div>
            <div>{{ $profile['graduation_year'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Location</div>
            <div>
              @if ($profile['city'] || $profile['country'])
                {{ collect([$profile['city'] ?? null, $profile['country'] ?? null])->filter()->implode(', ') }}
              @else
                —
              @endif
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Current Institution</div>
            <div>{{ $profile['current_institution'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Designation</div>
            <div>{{ $profile['designation'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Company</div>
            <div>{{ $profile['company'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Higher Studies</div>
            <div>{{ $profile['higher_studies'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Job Status</div>
            <div>{{ $profile['job_status'] ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-slate">Mentorship</div>
            <div>{{ ($profile['mentorship'] ?? false) ? 'Willing to mentor' : 'Not currently offering mentorship' }}</div>
          </div>
          <div class="col-12">
            <div class="small text-muted-slate">Skills</div>
            <div>{{ $profile['skills'] ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
