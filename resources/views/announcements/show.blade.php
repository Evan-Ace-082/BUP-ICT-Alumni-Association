@extends('layouts.app')

@section('title', $announcement->title.' — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">
        {{ $announcement->target_type === 'all' ? 'All Alumni' : ($announcement->batch?->batch_name ?? 'Batch Announcement') }}
      </div>
      <h1 class="mb-0">{{ $announcement->title }}</h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <a href="{{ route('announcements.index') }}" class="small d-inline-block mb-3">&larr; Back to Announcements</a>

      <div class="card-feature">
        <p class="small text-muted-slate mb-3">
          Published {{ $announcement->published_at?->format('F j, Y g:i A') }}
        </p>
        <div style="white-space: pre-line;">{{ $announcement->body }}</div>
      </div>
    </div>
  </section>
@endsection
