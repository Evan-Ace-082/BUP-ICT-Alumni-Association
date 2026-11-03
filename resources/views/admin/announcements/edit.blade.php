@extends('layouts.dashboard')
@section('title', 'Edit Announcement — BUP ICT Alumni Association')
@section('page_title', 'Edit Announcement')
@section('content')
  <a href="{{ route('admin.announcements.index') }}" class="small d-inline-block mb-3">&larr; Back to Announcements</a>
  <div class="card-feature">
    <h2 class="h6 mb-3">Edit Announcement</h2>
    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
      @csrf @method('PUT')
      @include('admin.announcements._form')
      <button type="submit" class="btn btn-brass mt-3">Save Changes</button>
    </form>
  </div>
@endsection
