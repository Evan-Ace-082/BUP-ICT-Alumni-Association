@extends('layouts.app')
@section('title', $album->title.' — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ route('gallery.index') }}" class="text-white-50 text-decoration-none small">&larr; Back to Gallery</a>
        @if ($album->batch)
          <span class="badge bg-brass text-white small">{{ $album->batch->batch_name }}</span>
        @endif
      </div>
      <h1 class="mb-2">{{ $album->title }}</h1>
      @if ($album->description)
        <p class="lead mb-0 small" style="opacity: 0.9; max-width: 48rem;">{{ $album->description }}</p>
      @endif
    </div>
  </section>

  <section class="section py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
          <span class="text-muted-slate small">
            <strong>{{ $album->photos->count() }}</strong> {{ \Illuminate\Support\Str::plural('photo', $album->photos->count()) }}
          </span>
        </div>
        @auth
          @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
            <a href="{{ route('admin.gallery.albums.edit', $album) }}" class="btn btn-sm btn-outline-ink">Manage Album</a>
          @elseif (auth()->user()->hasRole('batch-representative') && auth()->user()->assigned_batch_id === $album->batch_id)
            <a href="{{ route('batch-rep.gallery.albums.edit', $album) }}" class="btn btn-sm btn-outline-ink">Manage Album</a>
          @endif
        @endauth
      </div>

      @if ($album->photos->isEmpty())
        <div class="placeholder-panel py-5">
          <div class="mb-3" style="font-size: 2.5rem;">📷</div>
          <h2 class="h6 mb-2">No photos in this album yet</h2>
          <p class="small text-muted-slate mb-0">Photos added to this album will appear here once uploaded.</p>
        </div>
      @else
        <div class="row g-3 g-md-4">
          @foreach ($album->photos as $index => $photo)
            <div class="col-6 col-md-4 col-lg-3">
              <div class="photo-card h-100 d-flex flex-column">
                <button type="button"
                        class="btn p-0 border-0 text-start w-100 photo-trigger"
                        data-bs-toggle="modal"
                        data-bs-target="#photoLightboxModal"
                        data-photo-index="{{ $index }}"
                        data-photo-src="{{ asset('storage/'.$photo->image_path) }}"
                        data-photo-caption="{{ $photo->caption ?: $album->title }}"
                        aria-label="View larger image: {{ $photo->caption ?: $album->title }}">
                  <div class="position-relative overflow-hidden rounded border"
                       style="aspect-ratio: 1; background-color: var(--parchment-50, #f8f6f1);">
                    <img src="{{ asset('storage/'.$photo->image_path) }}"
                         alt="{{ $photo->caption ?: $album->title }}"
                         class="w-100 h-100 gallery-thumb"
                         style="object-fit: cover; transition: transform 0.25s ease;"
                         onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted-slate small\'><span style=\'font-size:1.5rem;\'>🖼️</span><span>Photo</span></div>';"
                         loading="lazy">
                  </div>
                </button>
                @if ($photo->caption)
                  <p class="small text-muted-slate mt-2 mb-0 text-truncate px-1" title="{{ $photo->caption }}">
                    {{ $photo->caption }}
                  </p>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- Lightbox Modal --}}
  <div class="modal fade" id="photoLightboxModal" tabindex="-1" aria-labelledby="photoLightboxLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content border-0 shadow-lg bg-dark text-white">
        <div class="modal-header border-0 pb-1">
          <span class="small text-white-50" id="photoLightboxCounter"></span>
          <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center p-2 p-md-4 position-relative">
          <div class="d-flex align-items-center justify-content-center" style="min-height: 50vh; max-height: 75vh;">
            <img id="photoLightboxImg"
                 src=""
                 alt="Enlarged photo"
                 class="img-fluid rounded shadow"
                 style="max-height: 75vh; max-width: 100%; object-fit: contain;">
          </div>
          <div class="mt-3">
            <p id="photoLightboxCaption" class="mb-0 small text-white-50"></p>
          </div>

          {{-- Previous / Next controls --}}
          <button type="button" class="btn btn-outline-light btn-sm position-absolute top-50 start-0 translate-middle-y ms-2 rounded-circle" id="lightboxPrevBtn" style="width: 40px; height: 40px; opacity: 0.85;" aria-label="Previous photo">&larr;</button>
          <button type="button" class="btn btn-outline-light btn-sm position-absolute top-50 end-0 translate-middle-y me-2 rounded-circle" id="lightboxNextBtn" style="width: 40px; height: 40px; opacity: 0.85;" aria-label="Next photo">&rarr;</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
<style>
  .photo-trigger:hover .gallery-thumb {
    transform: scale(1.04);
  }
  .photo-card {
    transition: transform 0.2s ease;
  }
  .photo-card:hover {
    transform: translateY(-2px);
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const triggers = Array.from(document.querySelectorAll('.photo-trigger'));
    if (!triggers.length) return;

    const modal = document.getElementById('photoLightboxModal');
    const img = document.getElementById('photoLightboxImg');
    const caption = document.getElementById('photoLightboxCaption');
    const counter = document.getElementById('photoLightboxCounter');
    const prevBtn = document.getElementById('lightboxPrevBtn');
    const nextBtn = document.getElementById('lightboxNextBtn');

    let currentIndex = 0;

    function showPhoto(index) {
      if (index < 0) index = triggers.length - 1;
      if (index >= triggers.length) index = 0;
      currentIndex = index;

      const trigger = triggers[currentIndex];
      const src = trigger.getAttribute('data-photo-src');
      const text = trigger.getAttribute('data-photo-caption') || '';

      img.src = src;
      caption.textContent = text;
      counter.textContent = `${currentIndex + 1} / ${triggers.length}`;

      if (triggers.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
      } else {
        prevBtn.style.display = 'block';
        nextBtn.style.display = 'block';
      }
    }

    triggers.forEach((trigger, idx) => {
      trigger.addEventListener('click', function () {
        showPhoto(idx);
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        showPhoto(currentIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        showPhoto(currentIndex + 1);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (!modal || !modal.classList.contains('show')) return;
      if (e.key === 'ArrowLeft') showPhoto(currentIndex - 1);
      if (e.key === 'ArrowRight') showPhoto(currentIndex + 1);
    });
  });
</script>
@endpush
