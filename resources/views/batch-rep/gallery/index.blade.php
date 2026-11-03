@extends('layouts.dashboard')
@section('title', 'My Batch Gallery — BUP ICT Alumni Association')
@section('page_title', 'My Batch Gallery')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">Albums</h2>
      <a href="{{ route('batch-rep.gallery.albums.create') }}" class="btn btn-brass">New Album</a>
    </div>
    @if ($albums->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No albums yet</h3></div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead><tr><th>Title</th><th>Photos</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @foreach ($albums as $album)
              <tr>
                <td>{{ $album->title }}</td>
                <td>{{ $album->photos_count }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($album->status) }}</span></td>
                <td class="d-flex gap-2">
                  <a href="{{ route('batch-rep.gallery.albums.edit', $album) }}" class="btn btn-sm btn-outline-ink">Manage</a>
                  @if ($album->status === 'draft')
                    <form method="POST" action="{{ route('batch-rep.gallery.albums.publish', $album) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-brass">Publish</button></form>
                  @else
                    <form method="POST" action="{{ route('batch-rep.gallery.albums.unpublish', $album) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Unpublish</button></form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $albums->links() }}</div>
    @endif
  </div>
@endsection
