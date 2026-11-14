@extends('layouts.dashboard')
@section('title', 'Student Verifications — Admin Panel')
@section('page_title', 'Current Student Verifications')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">BUP ICT Current Student Accounts</h1>
      <p class="small text-muted-slate mb-0">Verify currently enrolled student registrations to grant access to the Mentorship System.</p>
    </div>
    <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">Mentorship Overview &rarr;</a>
  </div>

  {{-- Status Tabs --}}
  <div class="d-flex flex-wrap gap-1 mb-4">
    <a href="{{ route('admin.students.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $currentStatus === 'pending' ? 'btn-brass' : 'btn-outline-ink' }}">
      Pending Verification
    </a>
    <a href="{{ route('admin.students.index', ['status' => 'verified']) }}" class="btn btn-sm {{ $currentStatus === 'verified' ? 'btn-brass' : 'btn-outline-ink' }}">
      Verified Students
    </a>
    <a href="{{ route('admin.students.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ $currentStatus === 'rejected' ? 'btn-brass' : 'btn-outline-ink' }}">
      Rejected
    </a>
    <a href="{{ route('admin.students.index', ['status' => 'all']) }}" class="btn btn-sm {{ $currentStatus === 'all' ? 'btn-brass' : 'btn-outline-ink' }}">
      All Records
    </a>
  </div>

  {{-- Search Bar --}}
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('admin.students.index') }}" class="row g-2">
      <input type="hidden" name="status" value="{{ $currentStatus }}">
      <div class="col-md-6">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, student ID, or email..." value="{{ $search ?? '' }}">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass btn-sm">Search</button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-ghost-sm btn-sm">Reset</a>
      </div>
    </form>
  </div>

  @if ($students->isEmpty())
    <div class="card-feature text-center py-5">
      <p class="small text-muted-slate mb-0">No student registration records found.</p>
    </div>
  @else
    <div class="card-feature p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted-slate">
              <th>Student</th>
              <th>Student ID</th>
              <th>Session &amp; Batch</th>
              <th>Enrollment &amp; Exp. Grad</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            @foreach ($students as $student)
              <tr>
                <td>
                  <div class="fw-semibold text-ink-900">{{ $student->full_name }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">{{ $student->email }}</div>
                </td>
                <td>
                  <span class="fw-semibold">{{ $student->student_id }}</span>
                </td>
                <td>
                  <div>Session: {{ $student->session ?: '—' }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">
                    {{ $student->batch ? $student->batch->batch_code : 'Unassigned' }}
                  </div>
                </td>
                <td>
                  <div>Enrolled: {{ $student->enrollment_year ?: '—' }}</div>
                  <div class="text-muted-slate" style="font-size: 11px;">Graduating: {{ $student->expected_graduation_year ?: '—' }}</div>
                </td>
                <td>
                  @if ($student->verification_status === 'verified')
                    <span class="badge bg-success">Verified</span>
                  @elseif ($student->verification_status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @else
                    <span class="badge bg-secondary">Rejected</span>
                  @endif
                  @if ($student->status === 'graduated')
                    <span class="badge bg-primary ms-1">Graduated</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-ink btn-sm py-1 px-2" style="font-size: 11px;">
                      Details
                    </a>
                    @if ($student->isPending())
                      <form method="POST" action="{{ route('admin.students.verify', $student) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-brass btn-sm py-1 px-2" style="font-size: 11px;" onclick="return confirm('Verify this student account?')">
                          Verify
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="p-3 border-top">
        {{ $students->links() }}
      </div>
    </div>
  @endif
@endsection
