@extends('layouts.dashboard')

@section('title', 'Add Batch — BUP ICT Alumni Association')
@section('page_title', 'Add New Batch')


@section('content')
  <a href="{{ route('admin.batches.index') }}" class="small d-inline-block mb-3">&larr; Back to Batches</a>

  <div class="card-feature">
    <h2 class="h6 mb-3">Add New Batch</h2>

    <form method="POST" action="{{ route('admin.batches.store') }}">
      @csrf
      @include('admin.batches._form')

      <button type="submit" class="btn btn-brass mt-3">Create Batch</button>
    </form>
  </div>
@endsection
