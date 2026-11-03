@extends('layouts.app')

@section('title', 'Alumni ID Verification — BUP ICT Alumni Association')

@section('content')
<div class="py-5 bg-light min-vh-75 d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-md-9">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          {{-- Header Bar --}}
          <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, var(--ink-900) 0%, var(--ink-700) 100%);">
            <div class="small text-uppercase tracking-wider text-warning mb-1 fw-semibold" style="letter-spacing: 0.1em;">
              Official Digital Credential Verification
            </div>
            <h1 class="h4 mb-0 fw-bold">BUP ICT Alumni Association</h1>
            <div class="small text-white-50 mt-1">
              Department of Information and Communication Technology &bull; BUP
            </div>
          </div>

          {{-- Verification Status Indicator --}}
          <div class="p-4 text-center border-bottom bg-white">
            @if ($isVerified)
              <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle mb-3" style="width: 64px; height: 64px; font-size: 32px;">
                ✓
              </div>
              <h2 class="h5 text-success fw-bold mb-1">Authentic Verified Alumni</h2>
              <p class="small text-muted mb-0">
                This digital ID is authentic and officially recognized in the active BUP ICT Alumni Association registry.
              </p>
            @elseif ($alumni->verification_status === 'rejected')
              <div class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle mb-3" style="width: 64px; height: 64px; font-size: 32px;">
                ✗
              </div>
              <h2 class="h5 text-danger fw-bold mb-1">Verification Not Approved</h2>
              <p class="small text-muted mb-0">
                This alumni record has not been approved or is currently deactivated.
              </p>
            @else
              <div class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mb-3" style="width: 64px; height: 64px; font-size: 32px;">
                ⏳
              </div>
              <h2 class="h5 text-warning fw-bold mb-1">Verification Pending</h2>
              <p class="small text-muted mb-0">
                This alumni account registration is currently pending administrator verification.
              </p>
            @endif
          </div>

          {{-- Verified Details --}}
          <div class="p-4 bg-white">
            <h3 class="h6 text-muted-slate text-uppercase small fw-bold mb-3" style="letter-spacing: 0.05em;">
              Alumni Verification Details
            </h3>

            <div class="list-group list-group-flush small">
              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Full Name</span>
                <span class="fw-bold text-dark fs-6">{{ $alumni->full_name }}</span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Digital Alumni ID</span>
                <span class="font-monospace fw-bold px-2 py-1 bg-light rounded text-dark">
                  {{ $alumniId ?: '—' }}
                </span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Student ID</span>
                <span class="font-monospace fw-semibold">{{ $maskedStudentId }}</span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Department</span>
                <span class="fw-semibold text-dark">{{ $department }}</span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Batch</span>
                <span class="fw-semibold text-dark">{{ $batchName }}</span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Graduation Year</span>
                <span class="fw-semibold text-dark">{{ $graduationYear }}</span>
              </div>

              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <span class="text-muted">Verification Status</span>
                <span>
                  @if ($isVerified)
                    <span class="badge bg-success">Verified Active</span>
                  @elseif ($alumni->verification_status === 'rejected')
                    <span class="badge bg-danger">Rejected</span>
                  @else
                    <span class="badge bg-warning text-dark">Pending Admin Approval</span>
                  @endif
                </span>
              </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded-3 text-muted-slate small">
              <div class="d-flex align-items-start gap-2">
                <span class="text-success fw-bold" style="font-size: 1.1rem;">🔒</span>
                <div>
                  <strong>Official Live Verification:</strong> This verification is queried in real time directly from the central database of the BUP ICT Alumni Association. Private contact information is withheld in compliance with institutional privacy standards.
                </div>
              </div>
            </div>

            <div class="mt-4 text-center">
              <a href="{{ route('home') }}" class="btn btn-sm btn-outline-ink">
                &larr; Return to Alumni Portal
              </a>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</div>
@endsection
