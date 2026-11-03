@extends('layouts.dashboard')
@section('title', 'Audit Log — BUP ICT Alumni Association')
@section('page_title', 'Audit Log')
@section('content')
  <div class="card-feature">
    <h2 class="h6 mb-3">Sensitive Admin-Management Actions</h2>
    @if ($logs->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No actions logged yet</h3></div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th></tr></thead>
          <tbody>
            @foreach ($logs as $log)
              <tr>
                <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                <td>{{ $log->actor?->name ?? '—' }}</td>
                <td>
                  {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                  @if($log->details)
                    <div class="text-muted-slate small">{{ $log->details }}</div>
                  @endif
                </td>
                <td>
                  @if($log->target instanceof \App\Models\User)
                    {{ $log->target->name }} (User)
                  @elseif($log->target instanceof \App\Models\Election)
                    {{ $log->target->title }} (Election)
                  @elseif($log->target instanceof \App\Models\Committee)
                    Term: {{ $log->target->term }} (Committee)
                  @else
                    {{ $log->target?->name ?? $log->target?->title ?? '—' }}
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $logs->links() }}</div>
    @endif
  </div>
@endsection
