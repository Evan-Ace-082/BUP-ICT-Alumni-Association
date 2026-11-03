@extends('layouts.dashboard')

@section('title', 'My Batch Alumni — BUP ICT Alumni Association')
@section('page_title', 'My Batch Alumni')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  <div class="card-feature mb-4">
    <h2 class="h6 mb-1">{{ $batch?->batch_name ?? 'No batch assigned' }}</h2>
    @if ($batch)
      <p class="small text-muted-slate mb-0">{{ $batch->batch_code }}</p>
    @else
      <p class="small text-muted-slate mb-0">
        Your account has no assigned batch yet — contact an administrator.
      </p>
    @endif
  </div>

  <div class="card-feature">
    <form method="GET" action="{{ route('batch-rep.alumni.index') }}" class="row g-2 align-items-end mb-3">
      <div class="col-md-4">
        <label class="form-label small" for="search">Search</label>
        <input type="text" name="search" id="search" class="form-control"
               placeholder="Student ID or name" value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-brass w-100">Filter</button>
      </div>
    </form>

    @if ($alumni->isEmpty())
      <div class="placeholder-panel">
        <h3 class="h6 mb-2">No alumni in this batch yet</h3>
        <p class="mb-0">Alumni records appear here once they're imported into this batch.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($alumni as $record)
              <tr>
                <td>{{ $record->student_id }}</td>
                <td>{{ $record->full_name }}</td>
                <td>{{ $record->email ?? '—' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($record->status) }}</span></td>
                <td>
                  <a href="{{ route('batch-rep.alumni.edit', $record) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-3">{{ $alumni->links() }}</div>
    @endif
  </div>
@endsection
