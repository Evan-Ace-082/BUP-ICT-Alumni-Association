@extends('layouts.dashboard')

@section('title', 'Import Master Alumni Excel — BUP ICT Alumni Association')
@section('page_title', 'Master Alumni Import')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <div class="card-feature mb-4">
    <h2 class="h6 mb-3">Upload Master Excel (dry run)</h2>
    <p class="small text-muted-slate mb-3">
      Uploading only previews the results below — nothing is written to the database until
      you review it and click Confirm Import.
    </p>
    <form method="POST" action="{{ route('admin.alumni-import.preview') }}" enctype="multipart/form-data">
      @csrf
      <div class="mb-3">
        <div class="form-check form-check-inline">
          <input type="radio" name="mode" value="master" id="mode-master" class="form-check-input" checked>
          <label class="form-check-label small" for="mode-master">
            Master import — writes any non-blank value from the file
          </label>
        </div>
        <div class="form-check form-check-inline">
          <input type="radio" name="mode" value="supplement" id="mode-supplement" class="form-check-input">
          <label class="form-check-label small" for="mode-supplement">
            Supplementary import — only fills currently blank fields, never overwrites existing data
          </label>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <input type="file" name="excel_file" class="form-control" style="max-width: 360px;" accept=".xlsx,.xls" required>
        <button type="submit" class="btn btn-brass">Preview / Dry Run</button>
      </div>
    </form>
  </div>

  @if ($report)
    <div class="card-feature mb-4">
      <h2 class="h6 mb-3">
        {{ $token ? 'Preview results' : 'Import results' }}
        @if ($fileName)
          <span class="text-muted-slate small fw-normal">— {{ $fileName }}</span>
        @endif
        @if (! empty($mode))
          <span class="badge bg-secondary fw-normal">{{ $mode === 'supplement' ? 'Supplementary' : 'Master' }} mode</span>
        @endif
      </h2>

      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['total_rows'] }}</div>
          <div class="stat-label">Total Rows</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['valid_rows'] }}</div>
          <div class="stat-label">Valid</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['invalid_rows'] }}</div>
          <div class="stat-label">Invalid</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['new'] }}</div>
          <div class="stat-label">New</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['existing'] }}</div>
          <div class="stat-label">Existing (update)</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['duplicates'] }}</div>
          <div class="stat-label">Duplicates in file</div>
        </div>
        <div class="col-6 col-md-3 stat-block">
          <div class="stat-number">{{ $report['unknown_batches'] }}</div>
          <div class="stat-label">Unknown batches</div>
        </div>
      </div>

      @if (! empty($report['errors']))
        <h3 class="h6 mb-2">Errors</h3>
        <div class="table-responsive mb-3" style="max-height: 320px; overflow-y: auto;">
          <table class="table table-sm small">
            <thead>
              <tr><th>Sheet</th><th>Row</th><th>Student ID</th><th>Reason</th></tr>
            </thead>
            <tbody>
              @foreach ($report['errors'] as $error)
                <tr>
                  <td>{{ $error['sheet'] ?? '—' }}</td>
                  <td>{{ $error['row'] }}</td>
                  <td>{{ $error['student_id'] ?? '—' }}</td>
                  <td>{{ $error['reason'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      @php
        $isClean = $report['invalid_rows'] === 0 && $report['duplicates'] === 0 && $report['unknown_batches'] === 0;
      @endphp

      @if ($token)
        @if ($isClean)
          <form method="POST" action="{{ route('admin.alumni-import.confirm') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-check mb-3">
              <input type="checkbox" name="confirmed" value="1" id="confirmed" class="form-check-input" required>
              <label class="form-check-label small" for="confirmed">
                I've reviewed the preview above and want to import the {{ $report['new'] }} new and
                {{ $report['existing'] }} existing record(s) shown as valid.
              </label>
            </div>
            <button type="submit" class="btn btn-brass">Confirm Import</button>
          </form>
        @else
          <div class="alert alert-warning small mb-0">
            Confirm Import is disabled until this file has zero invalid rows, zero duplicates,
            and zero unknown batches. Fix the issues above in the source Excel and re-run the
            preview.
          </div>
        @endif
      @endif
    </div>
  @endif

  <div class="card-feature">
    <h2 class="h6 mb-3">Import history</h2>
    @if ($history->isEmpty())
      <div class="placeholder-panel">No imports have been run yet.</div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small">
          <thead>
            <tr>
              <th>File</th><th>Status</th><th>Total</th><th>Success</th><th>Failed</th>
              <th>Imported by</th><th>When</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($history as $entry)
              <tr>
                <td>{{ $entry->file_name }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $entry->status)) }}</td>
                <td>{{ $entry->total_rows }}</td>
                <td>{{ $entry->successful_rows }}</td>
                <td>{{ $entry->failed_rows }}</td>
                <td>{{ $entry->importer?->name ?? '—' }}</td>
                <td>{{ $entry->imported_at?->diffForHumans() ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
