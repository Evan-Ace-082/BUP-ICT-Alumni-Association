@extends('layouts.app')
@section('title', $event->title.' — BUP ICT Alumni Association')
@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;"><div class="container"><div class="eyebrow mb-3">{{ $event->event_type }}</div><h1 class="mb-0">{{ $event->title }}</h1></div></section>
  <section class="section"><div class="container">
    <a href="{{ route('events.index') }}" class="small d-inline-block mb-3">&larr; Back to Events</a>
    <div class="card-feature">
      <p class="small text-muted-slate mb-3">{{ $event->event_date->format('F j, Y') }} @if($event->venue) — {{ $event->venue }} @endif</p>
      <div style="white-space: pre-line;">{{ $event->description }}</div>
      @if ($event->registration_url)
        <p class="mt-3"><a href="{{ $event->registration_url }}" class="btn btn-brass">Register</a></p>
      @endif
    </div>
  </div></section>
@endsection
