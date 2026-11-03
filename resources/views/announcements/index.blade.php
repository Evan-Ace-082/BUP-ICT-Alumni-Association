@extends('layouts.app')

@section('title', 'Announcements — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">Announcements</div>
      <h1 class="mb-0">Latest news from the association</h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      @if ($announcements->isEmpty())
        <div class="placeholder-panel">
          <h2 class="h6 mb-2">No announcements yet</h2>
          <p class="mb-0">Check back soon.</p>
        </div>
      @else
        <div class="row g-3">
          @foreach ($announcements as $announcement)
            <div class="col-md-6">
              <a href="{{ route('announcements.show', $announcement) }}" class="text-decoration-none text-reset">
                <div class="card-feature h-100">
                  <div class="eyebrow mb-2">
                    {{ $announcement->target_type === 'all' ? 'All Alumni' : ($announcement->batch?->batch_name ?? 'Batch Announcement') }}
                  </div>
                  <h2 class="h6 mb-2">{{ $announcement->title }}</h2>
                  <p class="small text-muted-slate mb-2">{{ \Illuminate\Support\Str::limit($announcement->body, 140) }}</p>
                  <p class="small text-muted-slate mb-0">{{ $announcement->published_at?->format('M j, Y') }}</p>
                </div>
              </a>
            </div>
          @endforeach
        </div>

        <div class="mt-4">{{ $announcements->links() }}</div>
      @endif
    </div>
  </section>
@endsection
