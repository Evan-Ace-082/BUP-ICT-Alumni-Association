@extends('layouts.app')
@section('title', $job->title.' — BUP ICT Alumni Association')
@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;"><div class="container"><div class="eyebrow mb-3">{{ $job->employment_type }}</div><h1 class="mb-0">{{ $job->title }}</h1></div></section>
  <section class="section"><div class="container">
    <a href="{{ route('jobs.index') }}" class="small d-inline-block mb-3">&larr; Back to Job Board</a>
    <div class="card-feature">
      <p class="small text-muted-slate mb-3">{{ $job->company }} @if($job->location) — {{ $job->location }} @endif</p>
      <div style="white-space: pre-line;">{{ $job->description }}</div>
      @if ($job->application_url)
        <p class="mt-3"><a href="{{ $job->application_url }}" class="btn btn-brass">Apply</a></p>
      @elseif ($job->application_email)
        <p class="mt-3">Apply via: {{ $job->application_email }}</p>
      @endif
    </div>
  </div></section>
@endsection
