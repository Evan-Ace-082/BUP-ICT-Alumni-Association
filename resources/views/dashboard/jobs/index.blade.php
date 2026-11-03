@extends('layouts.dashboard')
@section('title', 'Job Board — BUP ICT Alumni Association')
@section('page_title', 'Job Board')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">My Postings</h2>
      <a href="{{ route('dashboard.jobs.create') }}" class="btn btn-brass">Post a Job</a>
    </div>
    @if ($myJobs->isEmpty())
      <p class="small text-muted-slate mb-0">You haven't posted any jobs yet.</p>
    @else
      @foreach ($myJobs as $job)
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small">{{ $job->title }} — {{ $job->company }} <span class="badge bg-secondary">{{ ucfirst($job->status) }}</span></span>
          <span class="d-flex gap-2">
            <a href="{{ route('dashboard.jobs.edit', $job) }}" class="btn btn-sm btn-outline-ink">Edit</a>
            @if ($job->status === 'published')
              <form method="POST" action="{{ route('dashboard.jobs.close', $job) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Close</button></form>
            @endif
          </span>
        </div>
      @endforeach
    @endif
  </div>
  <div class="card-feature">
    <h2 class="h6 mb-3">Open Positions</h2>
    @if ($jobs->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No jobs posted yet</h3></div>
    @else
      @foreach ($jobs as $job)
        <div class="mb-3 pb-3" style="border-bottom: 1px solid #eee;">
          <div class="small text-muted-slate">{{ $job->employment_type }} — {{ $job->location }}</div>
          <div class="h6 mb-0">{{ $job->title }} at {{ $job->company }}</div>
        </div>
      @endforeach
      <div class="mt-3">{{ $jobs->links() }}</div>
    @endif
  </div>
@endsection
