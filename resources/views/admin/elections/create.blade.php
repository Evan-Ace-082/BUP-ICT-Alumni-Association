@extends('layouts.dashboard')

@section('title', 'Create Election — BUP ICT Alumni Association')
@section('page_title', 'Create Election')


@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card-feature">
        <h2 class="h5 mb-3 font-display">New Election Details</h2>

        @if($errors->any())
          <div class="alert alert-danger small mb-3">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('admin.elections.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label small fw-semibold">Election Title *</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="e.g. BUP ICT Alumni Executive Committee Election 2026-2028">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Election Type *</label>
              <select name="type" class="form-select" required>
                <option value="online" {{ old('type') === 'online' ? 'selected' : '' }}>Online Voting (Vote cast via platform)</option>
                <option value="physical" {{ old('type') === 'physical' ? 'selected' : '' }}>Physical Election (In-person ballot, manual winner selection)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Term / Period *</label>
              <input type="text" name="term" class="form-control" value="{{ old('term', '2026-2028') }}" required placeholder="e.g. 2026-2028">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Voting Start Date & Time *</label>
              <input type="datetime-local" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Voting End Date & Time *</label>
              <input type="datetime-local" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Description / Guidelines</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide election context, eligibility details, or voting instructions...">{{ old('description') }}</textarea>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-semibold mb-2">Pre-populate Suggested Positions (Optional)</label>
            <div class="row g-2">
              @foreach($suggestedPositions as $pos)
                <div class="col-sm-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="initial_positions[]" value="{{ $pos }}" id="pos_{{ $loop->index }}" checked>
                    <label class="form-check-label small" for="pos_{{ $loop->index }}">
                      {{ $pos }}
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
            <small class="text-muted-slate d-block mt-2">You can always add or adjust custom positions after creation.</small>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.elections.index') }}" class="btn btn-outline-ink btn-sm">Cancel</a>
            <button type="submit" class="btn btn-brass btn-sm">Create Election as Draft</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
