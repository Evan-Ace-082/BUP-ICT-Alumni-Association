@extends('layouts.dashboard')

@section('title', 'Create Executive Committee — BUP ICT Alumni Association')
@section('page_title', 'Create Executive Committee')


@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card-feature">
        <h2 class="h5 mb-3 font-display">New Executive Committee</h2>

        @if($errors->any())
          <div class="alert alert-danger small mb-3">
            <ul class="mb-0">
              @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('admin.committees.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label small fw-semibold">Term / Period *</label>
            <input type="text" name="term" class="form-control" value="{{ old('term', '2026-2028') }}" required placeholder="e.g. 2026-2028">
            <small class="text-muted-slate">The years or duration of this executive committee.</small>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="is_current" value="1" id="is_current" {{ old('is_current') ? 'checked' : '' }}>
            <label class="form-check-label small" for="is_current">
              Activate as Current Executive Committee (Will archive previous current committee)
            </label>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.committees.index') }}" class="btn btn-outline-ink btn-sm">Cancel</a>
            <button type="submit" class="btn btn-brass btn-sm">Create Committee</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
