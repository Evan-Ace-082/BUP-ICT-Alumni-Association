@extends('layouts.dashboard')
@section('title', 'My Batch Announcements — BUP ICT Alumni Association')
@section('page_title', 'My Batch Announcements')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
  <div class="card-feature mb-4"><h2 class="h6 mb-1">{{ $batch?->batch_name ?? 'No batch assigned' }}</h2></div>
  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h2 class="h6 mb-0">Announcements</h2>
      <a href="{{ route('batch-rep.announcements.create') }}" class="btn btn-brass">New Announcement</a>
    </div>
    @if ($announcements->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No announcements yet</h3></div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead><tr><th>Title</th><th>Target</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @foreach ($announcements as $announcement)
              <tr>
                <td>{{ $announcement->title }}</td>
                <td>{{ $announcement->target_type === 'all' ? 'All Alumni' : 'My Batch' }}</td>
                <td><span class="badge {{ $announcement->status === 'published' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($announcement->status) }}</span></td>
                <td class="d-flex gap-2">
                  @if ($announcement->target_type === 'batch')
                    <a href="{{ route('batch-rep.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                    @if ($announcement->status === 'draft')
                      <form method="POST" action="{{ route('batch-rep.announcements.publish', $announcement) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-brass">Publish</button></form>
                    @else
                      <form method="POST" action="{{ route('batch-rep.announcements.unpublish', $announcement) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Unpublish</button></form>
                    @endif
                  @else
                    <span class="small text-muted-slate">Managed by admin</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $announcements->links() }}</div>
    @endif
  </div>
@endsection
