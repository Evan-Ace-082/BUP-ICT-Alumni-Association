@extends('layouts.dashboard')
@section('title', 'Edit Announcement — BUP ICT Alumni Association')
@section('page_title', 'Edit Announcement')
@section('content')
  <a href="{{ route('batch-rep.announcements.index') }}" class="small d-inline-block mb-3">&larr; Back</a>
  <div class="card-feature">
    <h2 class="h6 mb-3">Edit Announcement</h2>
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('batch-rep.announcements.update', $announcement) }}">
      @csrf @method('PUT')
      <div class="mb-3"><label class="form-label small" for="title">Title</label><input type="text" name="title" id="title" class="form-control" value="{{ old('title', $announcement->title) }}" required></div>
      <div class="mb-3"><label class="form-label small" for="body">Body</label><textarea name="body" id="body" class="form-control" rows="5" required>{{ old('body', $announcement->body) }}</textarea></div>
      <button type="submit" class="btn btn-brass">Save Changes</button>
    </form>
  </div>
@endsection
