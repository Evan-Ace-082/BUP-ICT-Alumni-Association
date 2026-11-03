@extends('layouts.dashboard')

@section('title', $alumni->full_name.' — BUP ICT Alumni Association')
@section('page_title', 'Alumni Details')


@section('content')
  <a href="{{ route('admin.alumni.index') }}" class="small d-inline-block mb-3">&larr; Back to Alumni Directory</a>

  <div class="card-feature mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h2 class="h5 mb-1">{{ $alumni->full_name }}</h2>
        <p class="small text-muted-slate mb-0">Student ID: {{ $alumni->student_id }}</p>
      </div>
      <span class="badge bg-secondary">{{ ucfirst($alumni->status) }}</span>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="small text-muted-slate">Batch</div>
        <div>{{ $alumni->batch?->batch_name ?? '—' }} ({{ $alumni->batch?->batch_code ?? '—' }})</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Session / Graduation Year</div>
        <div>{{ $alumni->session ?? '—' }} @if($alumni->graduation_year) / {{ $alumni->graduation_year }} @endif</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Email</div>
        <div>{{ $alumni->email ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Phone</div>
        <div>{{ $alumni->phone ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Job / Institution</div>
        <div>{{ $alumni->current_institution ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Position</div>
        <div>{{ $alumni->designation ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Company</div>
        <div>{{ $alumni->company ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Higher Studies</div>
        <div>{{ $alumni->higher_studies ?? '—' }}</div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted-slate">Linked User Account</div>
        <div>{{ $alumni->user?->email ?? 'Not yet claimed' }}</div>
      </div>
    </div>
  </div>
@endsection
