@extends('layouts.dashboard')
@section('title', 'New Event — BUP ICT Alumni Association')
@section('page_title', 'New Event')
@section('content')
  <a href="{{ route('admin.events.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Type</label>
        <select name="event_type" class="form-select">@foreach (\App\Models\Event::TYPES as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>
      </div>
      <div class="mb-3"><label class="form-label small">Date</label><input type="date" name="event_date" class="form-control" value="{{ old('event_date') }}" required></div>
      <div class="mb-3"><label class="form-label small">Venue</label><input type="text" name="venue" class="form-control" value="{{ old('venue') }}"></div>
      <div class="mb-3">
        <label class="form-label small">Batch (leave blank for all alumni)</label>
        <select name="batch_id" class="form-select">
          <option value="">All Alumni</option>
          @foreach ($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->batch_name }}</option>@endforeach
        </select>
      </div>
      <div class="mb-3"><label class="form-label small">Cover Image</label><input type="file" name="cover_image" class="form-control" accept="image/*"></div>
      <button class="btn btn-brass">Save as Draft</button>
    </form>
  </div>
@endsection
