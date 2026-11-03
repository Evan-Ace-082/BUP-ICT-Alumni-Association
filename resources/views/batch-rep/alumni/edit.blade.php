@extends('layouts.dashboard')

@section('title', 'Edit '.$alumni->full_name.' — BUP ICT Alumni Association')
@section('page_title', 'Edit Alumni Record')


@section('content')
  <a href="{{ route('batch-rep.alumni.index') }}" class="small d-inline-block mb-3">&larr; Back to My Batch Alumni</a>

  <div class="card-feature">
    <h2 class="h6 mb-1">{{ $alumni->full_name }}</h2>
    <p class="small text-muted-slate mb-3">Student ID: {{ $alumni->student_id }} (not editable)</p>

    @if ($errors->any())
      <div class="alert alert-danger small">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('batch-rep.alumni.update', $alumni) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small" for="current_institution">Job / Institution</label>
          <input type="text" name="current_institution" id="current_institution" class="form-control"
                 value="{{ old('current_institution', $alumni->current_institution) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="designation">Position</label>
          <input type="text" name="designation" id="designation" class="form-control"
                 value="{{ old('designation', $alumni->designation) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="company">Company</label>
          <input type="text" name="company" id="company" class="form-control"
                 value="{{ old('company', $alumni->company) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="higher_studies">Higher Studies</label>
          <input type="text" name="higher_studies" id="higher_studies" class="form-control"
                 value="{{ old('higher_studies', $alumni->higher_studies) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="linkedin_url">LinkedIn URL</label>
          <input type="url" name="linkedin_url" id="linkedin_url" class="form-control"
                 value="{{ old('linkedin_url', $alumni->linkedin_url) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="facebook_url">Facebook URL</label>
          <input type="url" name="facebook_url" id="facebook_url" class="form-control"
                 value="{{ old('facebook_url', $alumni->facebook_url) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="blood_group">Blood Group</label>
          <input type="text" name="blood_group" id="blood_group" class="form-control"
                 value="{{ old('blood_group', $alumni->blood_group) }}">
        </div>
        <div class="col-12">
          <label class="form-label small" for="address">Address</label>
          <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $alumni->address) }}</textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-brass mt-3">Save Changes</button>
    </form>
  </div>
@endsection
