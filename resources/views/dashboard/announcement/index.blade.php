@extends('layouts.dashboard')

@section('title', 'Announcements — BUP ICT Alumni Association')
@section('page_title', 'Announcements')


@section('content')
  @if ($unreadNotifications->isNotEmpty())
    <div class="card-feature mb-4">
      <h2 class="h6 mb-2">Unread ({{ $unreadNotifications->count() }})</h2>
      @foreach ($unreadNotifications as $notification)
        <form method="POST" action="{{ route('dashboard.notifications.read', $notification->id) }}" class="d-flex justify-content-between align-items-center mb-2">
          @csrf
          <span class="small">{{ $notification->data['title'] ?? 'Announcement' }}</span>
          <button type="submit" class="btn btn-sm btn-outline-ink">Mark read</button>
        </form>
      @endforeach
    </div>
  @endif

  <div class="card-feature">
    <h2 class="h6 mb-3">All Announcements For You</h2>

    @if ($announcements->isEmpty())
      <div class="placeholder-panel">
        <h3 class="h6 mb-2">Nothing here yet</h3>
        <p class="mb-0">General announcements and announcements for your batch will appear here.</p>
      </div>
    @else
      @foreach ($announcements as $announcement)
        <div class="mb-3 pb-3" style="border-bottom: 1px solid #eee;">
          <div class="small text-muted-slate">
            {{ $announcement->target_type === 'all' ? 'All Alumni' : ($announcement->batch?->batch_name ?? 'Your Batch') }}
            — {{ $announcement->published_at?->format('M j, Y') }}
          </div>
          <a href="{{ route('announcements.show', $announcement) }}" class="h6 d-block mb-0">{{ $announcement->title }}</a>
        </div>
      @endforeach

      <div class="mt-3">{{ $announcements->links() }}</div>
    @endif
  </div>
@endsection
