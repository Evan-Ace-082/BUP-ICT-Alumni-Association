@extends('layouts.dashboard')

@section('title', 'Edit Executive Committee — BUP ICT Alumni Association')
@section('page_title', 'Edit Executive Committee')


@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card-feature">
        <h2 class="h5 mb-3 font-display">Edit Committee Term</h2>

        @if($errors->any())
          <div class="alert alert-danger small mb-3">
            <ul class="mb-0">
              @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('admin.committees.update', $committee) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-4">
            <label class="form-label small fw-semibold">Term / Period *</label>
            <input type="text" name="term" class="form-control" value="{{ old('term', $committee->term) }}" required>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.committees.show', $committee) }}" class="btn btn-outline-ink btn-sm">Cancel</a>
            <button type="submit" class="btn btn-brass btn-sm">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
