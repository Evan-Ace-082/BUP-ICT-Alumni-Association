@extends('layouts.dashboard')
@section('title', 'Events — BUP ICT Alumni Association')
@section('page_title', 'Events')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">Events</h2>
      <a href="{{ route('admin.events.create') }}" class="btn btn-brass">New Event</a>
    </div>
    @if ($events->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No events yet</h3></div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Target</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @foreach ($events as $event)
              <tr>
                <td>{{ $event->title }}</td>
                <td>{{ $event->event_type }}</td>
                <td>{{ $event->event_date->format('M j, Y') }}</td>
                <td>{{ $event->batch?->batch_code ?? 'All' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($event->status) }}</span></td>
                <td class="d-flex gap-2">
                  <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                  @if ($event->status === 'draft')
                    <form method="POST" action="{{ route('admin.events.publish', $event) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-brass">Publish</button></form>
                  @elseif ($event->status === 'published')
                    <form method="POST" action="{{ route('admin.events.unpublish', $event) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Unpublish</button></form>
                    <form method="POST" action="{{ route('admin.events.cancel', $event) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Cancel</button></form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $events->links() }}</div>
    @endif
  </div>
@endsection
