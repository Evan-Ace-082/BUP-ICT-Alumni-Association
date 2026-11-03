@extends('layouts.dashboard')

@section('title', 'Batch Management — BUP ICT Alumni Association')
@section('page_title', 'Batch Management')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h2 class="h6 mb-0">Batches</h2>
      <a href="{{ route('admin.batches.create') }}" class="btn btn-brass">Add New Batch</a>
    </div>

    @if ($batches->isEmpty())
      <div class="placeholder-panel">
        <h3 class="h6 mb-2">No batches yet</h3>
        <p class="mb-0">Add the department's batches here before importing the master alumni Excel.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead>
            <tr>
              <th>Batch Name</th>
              <th>Batch Code</th>
              <th>Session</th>
              <th>Graduation Year</th>
              <th>Status</th>
              <th>Alumni Count</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($batches as $batch)
              <tr>
                <td>{{ $batch->batch_name }}</td>
                <td>{{ $batch->batch_code }}</td>
                <td>{{ $batch->session ?? '—' }}</td>
                <td>{{ $batch->graduation_year ?? '—' }}</td>
                <td>
                  <span class="badge {{ $batch->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                    {{ ucfirst($batch->status) }}
                  </span>
                </td>
                <td>{{ $batch->alumni_count }}</td>
                <td class="d-flex gap-2">
                  <a href="{{ route('admin.batches.edit', $batch) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                  <form method="POST" action="{{ route('admin.batches.toggle-status', $batch) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-ink">
                      {{ $batch->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
