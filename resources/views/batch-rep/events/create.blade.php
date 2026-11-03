@extends('layouts.dashboard')
@section('title', 'New Batch Event — BUP ICT Alumni Association')
@section('page_title', 'New Event')
@section('content')
  <a href="{{ route('batch-rep.events.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    <p class="small text-muted-slate mb-3">This will be created for <strong>{{ $batch?->batch_name ?? 'your assigned batch' }}</strong> only.</p>
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('batch-rep.events.store') }}">
      @csrf
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Type</label>
        <select name="event_type" class="form-select">@foreach (\App\Models\Event::TYPES as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>
      </div>
      <div class="mb-3"><label class="form-label small">Date</label><input type="date" name="event_date" class="form-control" value="{{ old('event_date') }}" required></div>
      <div class="mb-3"><label class="form-label small">Venue</label><input type="text" name="venue" class="form-control" value="{{ old('venue') }}"></div>
      <button class="btn btn-brass">Save as Draft</button>
    </form>
  </div>
@endsection
