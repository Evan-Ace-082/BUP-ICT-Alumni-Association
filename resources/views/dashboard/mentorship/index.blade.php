@extends('layouts.dashboard')
@section('title', 'Find a Mentor — BUP ICT Alumni Association')
@section('page_title', 'Find a Mentor')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif

  @if ($myRequests->isNotEmpty())
    <div class="card-feature mb-4">
      <h2 class="h6 mb-2">My Mentorship Requests</h2>
      @foreach ($myRequests as $req)
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small">
            @if ($req->mentee_id === auth()->id())
              To {{ $req->mentor->alumni->full_name ?? $req->mentor->name }}
            @else
              From {{ $req->mentee->alumni->full_name ?? $req->mentee->name }}
            @endif
            — <span class="badge bg-secondary">{{ ucfirst($req->status) }}</span>
          </span>
          <span class="d-flex gap-2">
            @if ($req->status === 'pending' && $req->mentor_id === auth()->id())
              <form method="POST" action="{{ route('dashboard.mentorship.requests.accept', $req) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-brass">Accept</button></form>
              <form method="POST" action="{{ route('dashboard.mentorship.requests.reject', $req) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Reject</button></form>
            @endif
            @if ($req->status === 'pending' && $req->mentee_id === auth()->id())
              <form method="POST" action="{{ route('dashboard.mentorship.requests.cancel', $req) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Cancel</button></form>
            @endif
          </span>
        </div>
      @endforeach
    </div>
  @endif

  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('dashboard.mentorship.index') }}" class="row g-2">
      <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Name" value="{{ $filters['search'] ?? '' }}"></div>
      <div class="col-md-3"><input type="text" name="company" class="form-control" placeholder="Company" value="{{ $filters['company'] ?? '' }}"></div>
      <div class="col-md-2"><input type="text" name="country" class="form-control" placeholder="Country" value="{{ $filters['country'] ?? '' }}"></div>
      <div class="col-md-2"><input type="text" name="city" class="form-control" placeholder="City" value="{{ $filters['city'] ?? '' }}"></div>
      <div class="col-md-2"><input type="text" name="skills" class="form-control" placeholder="Skills" value="{{ $filters['skills'] ?? '' }}"></div>
      <div class="col-12"><button class="btn btn-brass btn-sm">Search</button></div>
    </form>
  </div>

  <div class="card-feature">
    <h2 class="h6 mb-3">Available Mentors</h2>
    @if ($mentors->isEmpty())
      <div class="placeholder-panel"><h3 class="h6 mb-2">No mentors found</h3></div>
    @else
      <div class="row g-3">
        @foreach ($mentors as $mentor)
          <div class="col-md-4">
            <div class="card-feature h-100">
              <h3 class="h6 mb-1">{{ $mentor->full_name }}</h3>
              <p class="small text-muted-slate mb-1">{{ $mentor->company }}</p>
              <p class="small text-muted-slate mb-2">{{ $mentor->batch?->batch_code }}</p>
              <form method="POST" action="{{ route('dashboard.mentorship.request', $mentor) }}">
                @csrf
                <input type="text" name="message" class="form-control form-control-sm mb-2" placeholder="Short message (optional)">
                <button class="btn btn-sm btn-brass">Request Mentorship</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
      <div class="mt-3">{{ $mentors->links() }}</div>
    @endif
  </div>
@endsection
