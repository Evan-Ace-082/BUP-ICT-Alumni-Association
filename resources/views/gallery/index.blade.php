@extends('layouts.app')
@section('title', 'Gallery — BUP ICT Alumni Association')
@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;"><div class="container"><div class="eyebrow mb-3">Gallery</div><h1 class="mb-0">Moments from our community</h1></div></section>
  <section class="section"><div class="container">
    @if ($albums->isEmpty())
      <div class="placeholder-panel"><h2 class="h6 mb-2">No albums yet</h2></div>
    @else
      <div class="row g-3">
        @foreach ($albums as $album)
          <div class="col-md-4">
            <a href="{{ route('gallery.show', $album) }}" class="text-decoration-none text-reset">
              <div class="card-feature h-100">
                <h2 class="h6 mb-1">{{ $album->title }}</h2>
                <p class="small text-muted-slate mb-0">{{ $album->photos_count }} photos</p>
              </div>
            </a>
          </div>
        @endforeach
      </div>
      <div class="mt-4">{{ $albums->links() }}</div>
    @endif
  </div></section>
@endsection
