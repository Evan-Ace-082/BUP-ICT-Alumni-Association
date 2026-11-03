@extends('layouts.app')
@section('title', 'Job Board — BUP ICT Alumni Association')
@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;"><div class="container"><div class="eyebrow mb-3">Job Board</div><h1 class="mb-0">Opportunities from our alumni network</h1></div></section>
  <section class="section"><div class="container">
    @if ($jobs->isEmpty())
      <div class="placeholder-panel"><h2 class="h6 mb-2">No jobs posted yet</h2></div>
    @else
      <div class="row g-3">
        @foreach ($jobs as $job)
          <div class="col-md-6">
            <a href="{{ route('jobs.show', $job) }}" class="text-decoration-none text-reset">
              <div class="card-feature h-100">
                <div class="eyebrow mb-2">{{ $job->employment_type }}</div>
                <h2 class="h6 mb-1">{{ $job->title }}</h2>
                <p class="small text-muted-slate mb-0">{{ $job->company }} @if($job->location) — {{ $job->location }} @endif</p>
              </div>
            </a>
          </div>
        @endforeach
      </div>
      <div class="mt-4">{{ $jobs->links() }}</div>
    @endif
  </div></section>
@endsection
