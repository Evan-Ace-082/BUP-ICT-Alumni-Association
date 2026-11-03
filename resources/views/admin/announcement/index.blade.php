@extends('layouts.dashboard')

@section('title', 'Announcements — BUP ICT Alumni Association')
@section('page_title', 'Announcements')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h2 class="h6 mb-0">Announcements</h2>
      <a href="{{ route('admin.announcements.create') }}" class="btn btn-brass">New Announcement</a>
    </div>

    @if ($announcements->isEmpty())
      <div class="placeholder-panel">
        <h3 class="h6 mb-2">No announcements yet</h3>
        <p class="mb-0">Create the first announcement using the button above.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead>
            <tr>
              <th>Title</th>
              <th>Target</th>
              <th>Public</th>
              <th>Status</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($announcements as $announcement)
              <tr>
                <td>{{ $announcement->title }}</td>
                <td>
                  @if ($announcement->target_type === 'all')
                    All Alumni
                  @else
                    {{ $announcement->batch?->batch_code ?? '—' }}
                  @endif
                </td>
                <td>{{ $announcement->is_public ? 'Yes' : 'No' }}</td>
                <td>
                  <span class="badge {{ $announcement->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                    {{ ucfirst($announcement->status) }}
                  </span>
                </td>
                <td>{{ $announcement->created_at->format('M j, Y') }}</td>
                <td class="d-flex gap-2">
                  <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                  @if ($announcement->status === 'draft')
                    <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-brass">Publish</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.announcements.unpublish', $announcement) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-outline-ink">Unpublish</button>
                    </form>
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
