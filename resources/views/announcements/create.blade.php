@extends('layouts.dashboard')

@section('title', 'New Announcement — BUP ICT Alumni Association')
@section('page_title', 'New Announcement')


@section('content')
  <a href="{{ route('admin.announcements.index') }}" class="small d-inline-block mb-3">&larr; Back to Announcements</a>

  <div class="card-feature">
    <h2 class="h6 mb-3">New Announcement</h2>

    <form method="POST" action="{{ route('admin.announcements.store') }}">
      @csrf
      @include('admin.announcements._form', ['announcement' => new \App\Models\Announcement])

      <button type="submit" class="btn btn-brass mt-3">Save as Draft</button>
    </form>
  </div>
@endsection
