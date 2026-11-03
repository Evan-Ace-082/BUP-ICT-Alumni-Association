@extends('layouts.dashboard')
@section('title', 'Manage Album — BUP ICT Alumni Association')
@section('page_title', 'Manage Album')
@section('content')
  <a href="{{ route('batch-rep.gallery.albums.index') }}" class="small d-inline-block mb-3">&larr; Back</a>

  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">
        {{ $album->title }}
        <span class="badge {{ $album->status === 'published' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($album->status) }}</span>
      </h2>
      @if ($album->status === 'draft')
        <form method="POST" action="{{ route('batch-rep.gallery.albums.publish', $album) }}">
          @csrf @method('PATCH')
          <button class="btn btn-brass">Publish Album</button>
        </form>
      @else
        <form method="POST" action="{{ route('batch-rep.gallery.albums.unpublish', $album) }}">
          @csrf @method('PATCH')
          <button class="btn btn-outline-ink">Unpublish Album</button>
        </form>
      @endif
    </div>
    @if ($album->status === 'draft')
      <p class="small text-muted-slate mb-0">This album is a draft — it will not appear on the public gallery until you publish it.</p>
    @else
      <p class="small text-muted-slate mb-0">This album is live on the public gallery.</p>
    @endif
  </div>

  <div class="card-feature mb-4">
    @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('batch-rep.gallery.albums.update', $album) }}">
      @csrf @method('PUT')
      <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" value="{{ old('title', $album->title) }}" required></div>
      <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $album->description) }}</textarea></div>
      <button class="btn btn-brass">Save Changes</button>
    </form>
  </div>

  <div class="card-feature">
    <h2 class="h6 mb-3">Photos ({{ $album->photos->count() }})</h2>
    <form method="POST" action="{{ route('batch-rep.gallery.albums.photos.store', $album) }}" enctype="multipart/form-data" class="mb-4">
      @csrf
      <label class="form-label small">Upload a new photo</label>
      <input type="file" name="image" class="form-control" accept="image/*" required>
      <button class="btn btn-sm btn-brass mt-2">Upload Photo</button>
    </form>

    @if ($album->photos->isEmpty())
      <p class="small text-muted-slate mb-0">No photos uploaded to this album yet.</p>
    @else
      <div class="row g-3">
        @foreach ($album->photos as $photo)
          <div class="col-6 col-md-3">
            <img src="{{ asset('storage/'.$photo->image_path) }}" alt="{{ $photo->caption }}" class="img-fluid rounded mb-1" style="aspect-ratio: 1; object-fit: cover; width: 100%;">
            <form method="POST" action="{{ route('batch-rep.gallery.albums.photos.destroy', [$album, $photo]) }}" onsubmit="return confirm('Remove this photo?');">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-outline-ink w-100">Remove</button>
            </form>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection
