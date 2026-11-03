@extends('layouts.dashboard')

@section('title', 'Alumni Directory — BUP ICT Alumni Association')
@section('page_title', 'Alumni Directory')


@section('content')
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('admin.alumni.index') }}" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small" for="search">Search</label>
        <input
          type="text"
          name="search"
          id="search"
          class="form-control"
          placeholder="Student ID, name, or email"
          value="{{ $filters['search'] ?? '' }}"
        >
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="batch_id">Batch</label>
        <select name="batch_id" id="batch_id" class="form-select">
          <option value="">All batches</option>
          @foreach ($batches as $batch)
            <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? null) == $batch->id)>
              {{ $batch->batch_name }} ({{ $batch->batch_code }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="status">Status</label>
        <select name="status" id="status" class="form-select">
          <option value="">All statuses</option>
          @foreach (['unclaimed' => 'Unclaimed', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
            <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-brass w-100">Filter</button>
        @if (array_filter($filters))
          <a href="{{ route('admin.alumni.index') }}" class="btn btn-outline-ink">Clear</a>
        @endif
      </div>
    </form>
  </div>

  <div class="card-feature">
    <h2 class="h6 mb-3">
      Alumni records
      <span class="text-muted-slate small fw-normal">— {{ $alumni->total() }} total</span>
    </h2>

    @if ($alumni->isEmpty())
      <div class="placeholder-panel">
        @if (array_filter($filters))
          <h3 class="h6 mb-2">No matching alumni records</h3>
          <p class="mb-0">Try a different search term or clear the filters above.</p>
        @else
          <h3 class="h6 mb-2">No alumni records yet</h3>
          <p class="mb-0">
            The alumni table is currently empty. Records will appear here once the master
            Excel file is imported.
          </p>
        @endif
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Batch</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Job / Institution</th>
              <th>Position</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($alumni as $record)
              <tr>
                <td>{{ $record->student_id }}</td>
                <td>{{ $record->full_name }}</td>
                <td>{{ $record->batch?->batch_code ?? '—' }}</td>
                <td>{{ $record->email ?? '—' }}</td>
                <td>{{ $record->phone ?? '—' }}</td>
                <td>{{ $record->current_institution ?? '—' }}</td>
                <td>{{ $record->designation ?? '—' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($record->status) }}</span></td>
                <td>
                  <a href="{{ route('admin.alumni.show', $record) }}" class="btn btn-sm btn-outline-ink">
                    View Details
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $alumni->links() }}
      </div>
    @endif
  </div>
@endsection
