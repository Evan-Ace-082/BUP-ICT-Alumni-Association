@extends('layouts.dashboard')

@section('title', 'Create Achievement Category — BUP ICT Alumni Association')
@section('page_title', 'Create Achievement Category')

@section('content')
  <a href="{{ route('admin.achievement-categories.index') }}" class="small d-inline-block mb-3 text-decoration-none text-muted-slate">&larr; Back to Categories</a>

  <div class="card-feature p-4" style="max-width: 680px;">
    <div class="mb-4">
      <h2 class="h5 mb-1">Create Achievement Sector</h2>
      <p class="small text-muted mb-0">Add a new industry sector or classification category for alumni achievements.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger small mb-4">
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.achievement-categories.store') }}">
      @csrf

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="name">Category / Sector Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Artificial Intelligence & Data Science" required>
        <div class="form-text small">Must be unique (case-insensitive).</div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="description">Description (Optional)</label>
        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief description of this sector or who should select it...">{{ old('description') }}</textarea>
      </div>

      <div class="mb-4 form-check">
        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" @checked(old('is_active', true))>
        <label class="form-check-label small" for="is_active">
          Active immediately (visible in dropdowns and public filters)
        </label>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-brass">Create Category</button>
        <a href="{{ route('admin.achievement-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
@endsection
