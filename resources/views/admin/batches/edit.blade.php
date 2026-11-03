@extends('layouts.dashboard')

@section('title', 'Edit Batch — BUP ICT Alumni Association')
@section('page_title', 'Edit Batch')


@section('content')
  <a href="{{ route('admin.batches.index') }}" class="small d-inline-block mb-3">&larr; Back to Batches</a>

  <div class="card-feature">
    <h2 class="h6 mb-3">Edit Batch — {{ $batch->batch_name }}</h2>

    <form method="POST" action="{{ route('admin.batches.update', $batch) }}">
      @csrf
      @method('PUT')
      @include('admin.batches._form')

      <button type="submit" class="btn btn-brass mt-3">Save Changes</button>
    </form>
  </div>
@endsection
