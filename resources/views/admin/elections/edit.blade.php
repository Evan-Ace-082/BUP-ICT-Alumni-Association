@extends('layouts.dashboard')

@section('title', 'Edit Election — BUP ICT Alumni Association')
@section('page_title', 'Edit Election')


@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card-feature">
        <h2 class="h5 mb-3 font-display">Edit Election Details</h2>

        @if(session('error'))
          <div class="alert alert-danger small mb-3">{{ session('error') }}</div>
        @endif
        @if(session('success'))
          <div class="alert alert-success small mb-3">{{ session('success') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger small mb-3">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @php
          $isFinalized = $election->isFinalized();
          $votesCount = $election->votes_count ?? $election->votes()->count();
          $hasVotes = $votesCount > 0;
        @endphp

        @if($isFinalized)
          <div class="alert alert-info small mb-4">
            <div class="fw-bold mb-1">ℹ️ Certified &amp; Finalized Election (Read-Only Mode)</div>
            <div>
              This election concluded and was officially certified on {{ $election->finalized_at?->format('M j, Y g:i A') ?? 'finalization' }}.
              To protect constitutional integrity, certified vote records, and official committee formations, election parameters cannot be altered.
            </div>
          </div>
        @elseif($hasVotes || $election->isOngoing())
          <div class="alert alert-warning small mb-4">
            <div class="fw-bold mb-1">⚠️ Active / Voted Election Restrictions</div>
            <div>
              This election {{ $election->isOngoing() ? 'is currently active/ongoing' : 'has recorded votes' }}
              ({{ $votesCount }} {{ Str::plural('vote', $votesCount) }} recorded).
              The election type (<strong>{{ ucfirst($election->type) }}</strong>) is locked because voter ballots are bound to this configuration.
              Schedule adjustments should be made with caution.
            </div>
          </div>
        @endif

        <form action="{{ route('admin.elections.update', $election) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label small fw-semibold">Election Title *</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $election->title) }}" {{ $isFinalized ? 'disabled' : 'required' }}>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Election Type *</label>
              @if($hasVotes || $isFinalized || ! $election->isDraft())
                <input type="text" class="form-control bg-light" value="{{ $election->type === 'online' ? 'Online Voting' : 'Physical Election' }}" disabled>
                <input type="hidden" name="type" value="{{ $election->type }}">
                <div class="form-text small text-muted">Type is locked once an election has votes or leaves draft status.</div>
              @else
                <select name="type" class="form-select" required>
                  <option value="online" {{ old('type', $election->type) === 'online' ? 'selected' : '' }}>Online Voting</option>
                  <option value="physical" {{ old('type', $election->type) === 'physical' ? 'selected' : '' }}>Physical Election</option>
                </select>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Term / Period *</label>
              <input type="text" name="term" class="form-control" value="{{ old('term', $election->term) }}" {{ $isFinalized ? 'disabled' : 'required' }}>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Voting Start Date & Time *</label>
              <input type="datetime-local" name="start_time" class="form-control" value="{{ old('start_time', $election->start_time->format('Y-m-d\TH:i')) }}" {{ $isFinalized ? 'disabled' : 'required' }}>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Voting End Date & Time *</label>
              <input type="datetime-local" name="end_time" class="form-control" value="{{ old('end_time', $election->end_time->format('Y-m-d\TH:i')) }}" {{ $isFinalized ? 'disabled' : 'required' }}>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-semibold">Description / Guidelines</label>
            <textarea name="description" class="form-control" rows="4" {{ $isFinalized ? 'disabled' : '' }}>{{ old('description', $election->description) }}</textarea>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.elections.show', $election) }}" class="btn btn-outline-ink btn-sm">
              {{ $isFinalized ? 'Back to Election' : 'Cancel' }}
            </a>
            @if(! $isFinalized)
              <button type="submit" class="btn btn-brass btn-sm">Save Changes</button>
            @endif
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
