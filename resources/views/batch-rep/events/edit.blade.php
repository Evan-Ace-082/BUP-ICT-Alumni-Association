@extends('layouts.dashboard')
@section('title', 'Edit Event — BUP ICT Alumni Association')
@section('page_title', 'Edit Event')
@section('content')
  <a href="{{ route('batch-rep.events.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('batch-rep.events.update', $event) }}">
      @csrf @method('PUT')
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description', $event->description) }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Type</label>
        <select name="event_type" class="form-select">@foreach (\App\Models\Event::TYPES as $type)<option value="{{ $type }}" @selected($event->event_type === $type)>{{ $type }}</option>@endforeach</select>
      </div>
      <div class="mb-3"><label class="form-label small">Date</label><input type="date" name="event_date" class="form-control" value="{{ old('event_date', $event->event_date->format('Y-m-d')) }}" required></div>
      <button class="btn btn-brass">Save Changes</button>
    </form>
  </div>
@endsection
