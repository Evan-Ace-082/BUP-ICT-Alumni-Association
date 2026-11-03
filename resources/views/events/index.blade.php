@extends('layouts.app')
@section('title', 'Events — BUP ICT Alumni Association')
@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;"><div class="container"><div class="eyebrow mb-3">Events</div><h1 class="mb-0">Upcoming alumni events</h1></div></section>
  <section class="section"><div class="container">
    @if ($events->isEmpty())
      <div class="placeholder-panel"><h2 class="h6 mb-2">No events yet</h2></div>
    @else
      <div class="row g-3">
        @foreach ($events as $event)
          <div class="col-md-6">
            <a href="{{ route('events.show', $event) }}" class="text-decoration-none text-reset">
              <div class="card-feature h-100">
                <div class="eyebrow mb-2">{{ $event->event_type }} — {{ $event->batch?->batch_name ?? 'All Alumni' }}</div>
                <h2 class="h6 mb-1">{{ $event->title }}</h2>
                <p class="small text-muted-slate mb-0">{{ $event->event_date->format('M j, Y') }} @if($event->venue) — {{ $event->venue }} @endif</p>
              </div>
            </a>
          </div>
        @endforeach
      </div>
      <div class="mt-4">{{ $events->links() }}</div>
    @endif
  </div></section>
@endsection
