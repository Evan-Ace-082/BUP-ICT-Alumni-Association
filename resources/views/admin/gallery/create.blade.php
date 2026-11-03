@extends('layouts.dashboard')
@section('title', 'New Album — BUP ICT Alumni Association')
@section('page_title', 'New Album')
@section('content')
  <a href="{{ route('admin.gallery.albums.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.gallery.albums.store') }}">
      @csrf
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
      <div class="mb-3">
        <label class="form-label small">Batch (leave blank for all alumni)</label>
        <select name="batch_id" class="form-select">
          <option value="">All Alumni</option>
          @foreach ($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->batch_name }}</option>@endforeach
        </select>
      </div>
      <button class="btn btn-brass">Create Album</button>
    </form>
  </div>
@endsection
