@extends('layouts.dashboard')
@section('title', 'New Album — BUP ICT Alumni Association')
@section('page_title', 'New Album')
@section('content')
  <a href="{{ route('batch-rep.gallery.albums.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    <p class="small text-muted-slate mb-3">Created for <strong>{{ $batch?->batch_name ?? 'your assigned batch' }}</strong> only.</p>
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('batch-rep.gallery.albums.store') }}">
      @csrf
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
      <button class="btn btn-brass">Create Album</button>
    </form>
  </div>
@endsection
