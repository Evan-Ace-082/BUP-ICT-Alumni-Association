@extends('layouts.dashboard')

@section('title', 'My Achievements — BUP ICT Alumni Association')
@section('page_title', 'My Achievements')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger small mb-4">{{ session('error') }}</div>
  @endif

  <div class="card-feature p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
      <div>
        <h2 class="h5 mb-1">My Submitted Achievements</h2>
        <p class="small text-muted mb-0">Track the status of your submitted recognitions, awards, and milestones.</p>
      </div>
      @if ($isEligible ?? ($alumni && $alumni->canLogIn()))
        <a href="{{ route('dashboard.achievements.create') }}" class="btn btn-brass">
          Add Achievement
        </a>
      @endif
    </div>

    @if (! $alumni)
      <div class="placeholder-panel p-4 text-center">
        <h3 class="h6 mb-2">No linked alumni record</h3>
        <p class="small text-muted mb-3" style="max-width: 600px; margin: 0 auto;">
          Achievement submission is only available to accounts linked to a verified alumni
          record. This account isn't currently linked to one. If you believe this is a mistake, contact the department.
        </p>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Return to Dashboard</a>
      </div>
    @elseif (! $alumni->canLogIn())
      <div class="placeholder-panel p-4 text-center">
        <h3 class="h6 mb-2">Alumni verification pending</h3>
        <p class="small text-muted mb-3">
          Achievement submission requires an approved and active alumni record. Your linked profile is currently pending verification or inactive.
        </p>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Return to Dashboard</a>
      </div>
    @elseif ($achievements->isEmpty())
      <div class="placeholder-panel p-5 text-center">
        <h3 class="h6 mb-2">No achievements submitted yet</h3>
        <p class="small text-muted mb-3">Share your professional milestones, awards, research publications, or community contributions.</p>
        <a href="{{ route('dashboard.achievements.create') }}" class="btn btn-sm btn-brass">Submit Your First Achievement</a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover table-sm small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" style="min-width: 200px;">Title</th>
              <th scope="col">Sector / Category</th>
              <th scope="col">Organization</th>
              <th scope="col">Date</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($achievements as $achievement)
              @php
                $catName = $achievement->category->name ?? $achievement->achievement_type ?? '—';
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold text-dark">{{ $achievement->title }}</div>
                  @if ($achievement->image)
                    <span class="badge bg-light text-secondary border small mt-1">Image Attached</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary">{{ $catName }}</span>
                </td>
                <td class="text-muted">{{ $achievement->organization ?: '—' }}</td>
                <td class="text-muted">{{ $achievement->achievement_date ? $achievement->achievement_date->format('M d, Y') : '—' }}</td>
                <td>
                  @if ($achievement->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @elseif ($achievement->status === 'rejected')
                    <span class="badge bg-danger">Rejected</span>
                  @else
                    <span class="badge bg-warning text-dark">Pending Review</span>
                  @endif
                </td>
                <td class="text-end">
                  @if ($achievement->status === 'pending')
                    <div class="d-inline-flex gap-1">
                      <a href="{{ route('dashboard.achievements.edit', $achievement) }}" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                      <form method="POST" action="{{ route('dashboard.achievements.destroy', $achievement) }}" onsubmit="return confirm('Are you sure you want to delete this pending achievement?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                      </form>
                    </div>
                  @else
                    <span class="text-muted small">Locked</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-4">{{ $achievements->links() }}</div>
    @endif
  </div>
@endsection
