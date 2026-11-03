@extends('layouts.dashboard')
@section('title', 'Job Management — BUP ICT Alumni Association')
@section('page_title', 'Job Management')
@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="card-feature mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <h2 class="h6 mb-0">All Job Listings</h2>
        <p class="small text-muted-slate mb-0">Manage job postings published by alumni and administrators.</p>
      </div>
      <a href="{{ route('admin.jobs.create') }}" class="btn btn-brass">Add Job Listing</a>
    </div>

    @if ($jobs->isEmpty())
      <div class="placeholder-panel py-4">
        <h3 class="h6 mb-2">No jobs posted yet</h3>
        <p class="small text-muted-slate mb-3">Create job listings directly as an administrator to share opportunities with alumni.</p>
        <a href="{{ route('admin.jobs.create') }}" class="btn btn-sm btn-brass">Add Job Listing</a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle">
          <thead>
            <tr>
              <th>Title</th>
              <th>Company</th>
              <th>Employment</th>
              <th>Posted By</th>
              <th>Status</th>
              <th>Deadline</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($jobs as $job)
              <tr>
                <td>
                  <strong>{{ $job->title }}</strong>
                  @if ($job->location)<div class="text-muted-slate small">{{ $job->location }}</div>@endif
                </td>
                <td>{{ $job->company }}</td>
                <td>{{ $job->employment_type }}</td>
                <td>
                  {{ $job->poster->name ?? '—' }}
                  @if ($job->poster && ($job->poster->hasRole('admin') || $job->poster->hasRole('super-admin')))
                    <span class="badge bg-secondary ms-1">Admin</span>
                  @endif
                </td>
                <td>
                  @if ($job->status === 'published')
                    <span class="badge bg-success">Published</span>
                  @elseif ($job->status === 'draft')
                    <span class="badge bg-secondary">Draft</span>
                  @else
                    <span class="badge bg-dark">Closed</span>
                  @endif
                </td>
                <td>{{ $job->deadline ? $job->deadline->format('M d, Y') : '—' }}</td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1 align-items-center">
                    <a href="{{ route('admin.jobs.edit', $job) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                    @if ($job->status !== 'published')
                      <form method="POST" action="{{ route('admin.jobs.publish', $job) }}" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-brass">Publish</button>
                      </form>
                    @else
                      <form method="POST" action="{{ route('admin.jobs.close', $job) }}" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-ink">Close</button>
                      </form>
                    @endif
                    <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="d-inline" onsubmit="return confirm('Remove this job listing permanently?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-ink">Remove</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $jobs->links() }}</div>
    @endif
  </div>
@endsection
