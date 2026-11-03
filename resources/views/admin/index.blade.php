@extends('layouts.dashboard')

@section('title', 'Admin — BUP ICT Alumni Association')
@section('page_title', 'Admin Overview')


@section('content')
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="card-feature stat-block">
        <div class="stat-number">{{ $batchCount }}</div>
        <div class="stat-label">Batches</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card-feature stat-block">
        <div class="stat-number">{{ $alumniCount }}</div>
        <div class="stat-label">Alumni Records</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card-feature stat-block">
        <div class="stat-number">{{ $pendingVerificationCount }}</div>
        <div class="stat-label">Pending Verifications</div>
      </div>
    </div>
  </div>

  <div class="placeholder-panel">
    <h2 class="h5 mb-2">Welcome, {{ auth()->user()->name }}</h2>
    <p class="mb-0">
      Use the sidebar to manage batches, alumni records, announcements, events, the gallery,
      achievements, jobs, and Excel imports.
      @if (auth()->user()->hasRole('super-admin'))
        As a Super Admin, you also have access to Admin Management and the Audit Log under
        System Administration.
      @endif
    </p>
  </div>
@endsection
