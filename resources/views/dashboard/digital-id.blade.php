@extends('layouts.dashboard')

@section('title', 'My Digital Alumni ID — BUP ICT Alumni Association')
@section('page_title', 'My Digital Alumni ID')

@section('content')
  @if (! $alumni)
    <div class="card-feature h-auto text-center p-5">
      <div class="fs-1 text-muted mb-2">🎓</div>
      <h2 class="h5 mb-2">No Alumni Profile Linked</h2>
      <p class="small text-muted-slate mb-0">Your account does not have a linked official alumni record.</p>
    </div>
  @elseif ($isPending)
    {{-- Pending Verification State --}}
    <div class="card-feature h-auto text-center p-5 mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mb-3" style="width: 72px; height: 72px; font-size: 36px;">
        ⏳
      </div>
      <h2 class="h4 fw-bold mb-2">Alumni Verification Pending</h2>
      <p class="text-muted-slate small mb-4 mx-auto" style="max-width: 540px;">
        Your email has been verified successfully. Your alumni verification is currently pending admin approval. Once an administrator verifies your graduation records, your official Digital Alumni ID and QR Code will be generated here automatically.
      </p>

      <div class="p-3 bg-light rounded-3 d-inline-block text-start small mb-4" style="max-width: 460px;">
        <div class="fw-bold mb-2 text-dark">Submitted Profile Review:</div>
        <div><strong>Name:</strong> {{ $alumni->full_name }}</div>
        <div><strong>Student ID:</strong> {{ $alumni->student_id }}</div>
        <div><strong>Batch:</strong> {{ $alumni->batch?->batch_name ?? '—' }}</div>
        <div><strong>Email:</strong> {{ $user->email }}</div>
        <div><strong>Status:</strong> <span class="badge bg-warning text-dark">Under Administrator Review</span></div>
      </div>

      <div>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-ink">
          &larr; Return to Dashboard
        </a>
      </div>
    </div>
  @elseif ($isRejected)
    {{-- Rejected State --}}
    <div class="card-feature h-auto text-center p-5 mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle mb-3" style="width: 72px; height: 72px; font-size: 36px;">
        ✗
      </div>
      <h2 class="h4 fw-bold text-danger mb-2">Alumni Verification Not Approved</h2>
      <p class="text-muted-slate small mb-3 mx-auto" style="max-width: 540px;">
        Your alumni verification request was reviewed and could not be approved at this time.
      </p>

      @if ($alumni->rejection_reason)
        <div class="alert alert-danger mx-auto text-start small mb-4" style="max-width: 500px;">
          <strong>Admin Remarks:</strong> {{ $alumni->rejection_reason }}
        </div>
      @endif

      <div class="d-flex justify-content-center gap-2">
        <a href="{{ route('dashboard.profile.edit') }}" class="btn btn-sm btn-brass">
          Review Profile Information
        </a>
        <a href="{{ route('contact') }}" class="btn btn-sm btn-outline-ink">
          Contact Support
        </a>
      </div>
    </div>
  @else
    {{-- Verified Digital ID Card --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div>
        <h2 class="h6 mb-0">Official Digital Membership Card</h2>
        <span class="small text-muted-slate">Issued by Department of Information &amp; Communication Technology, BUP</span>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-brass" onclick="window.print()">
          🖨️ Print Digital Card
        </button>
        @if ($alumni->qr_token)
          <a href="{{ $alumni->qrVerificationUrl() }}" target="_blank" class="btn btn-sm btn-outline-ink">
            🔗 Public Verification URL
          </a>
        @endif
      </div>
    </div>

    {{-- The Digital ID Card Container (Print-optimized) --}}
    <div class="row justify-content-center my-4">
      <div class="col-xl-8 col-lg-10">

        <div id="printable-alumni-card" class="alumni-digital-card position-relative text-white rounded-4 shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #0a1426 0%, #152542 50%, #0d1b33 100%); border: 2px solid #c89b3c;">

          {{-- Card Header --}}
          <div class="p-4 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-white p-1 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <span class="fw-bold text-dark fs-5">BUP</span>
              </div>
              <div>
                <div class="fw-bold text-uppercase fs-6 tracking-wide" style="letter-spacing: 0.05em; color: #f5f5f7;">
                  BUP ICT Alumni Association
                </div>
                <div class="small" style="color: #c89b3c;">
                  Department of Information and Communication Technology
                </div>
              </div>
            </div>
            <div class="text-end d-none d-sm-block">
              <span class="badge rounded-pill text-dark fw-bold px-3 py-2" style="background-color: #c89b3c;">
                OFFICIAL ALUMNI
              </span>
            </div>
          </div>

          {{-- Card Body --}}
          <div class="p-4">
            <div class="row g-4 align-items-center">

              {{-- Left Column: Photo & Alumni ID --}}
              <div class="col-sm-4 text-center">
                <div class="position-relative d-inline-block mb-3">
                  @if ($alumni->profile_photo_url)
                    <img
                      src="{{ $alumni->profile_photo_url }}"
                      alt="{{ $alumni->full_name }}"
                      class="rounded-circle border border-3 border-warning shadow"
                      style="width: 120px; height: 120px; object-fit: cover;"
                    >
                  @else
                    <div
                      class="rounded-circle border border-3 d-flex align-items-center justify-content-center mx-auto shadow"
                      style="width: 120px; height: 120px; background-color: #1f3354; border-color: #c89b3c !important; color: #f0c36d; font-size: 42px; font-weight: bold;"
                    >
                      {{ strtoupper(substr($alumni->full_name, 0, 1)) }}
                    </div>
                  @endif
                  <span class="position-absolute bottom-0 end-0 bg-success text-white rounded-circle p-1" title="Verified" style="width: 28px; height: 28px; font-size: 14px; line-height: 20px;">
                    ✓
                  </span>
                </div>

                <div class="small text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.08em;">
                  Digital Alumni ID
                </div>
                <div class="font-monospace fw-bold fs-5" style="color: #f0c36d;">
                  {{ $alumni->alumni_id }}
                </div>
              </div>

              {{-- Middle Column: Graduate Profile Data --}}
              <div class="col-sm-5">
                <h3 class="h4 fw-bold mb-3 text-white">{{ $alumni->full_name }}</h3>

                <div class="small mb-2">
                  <span class="text-secondary d-block" style="font-size: 0.75rem;">STUDENT ID</span>
                  <span class="font-monospace fw-bold fs-6 text-white">{{ $alumni->student_id }}</span>
                </div>

                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <span class="text-secondary d-block" style="font-size: 0.75rem;">BATCH</span>
                    <span class="fw-semibold text-white">{{ $alumni->batch?->batch_name ?? '—' }}</span>
                  </div>
                  <div class="col-6">
                    <span class="text-secondary d-block" style="font-size: 0.75rem;">PASSING YEAR</span>
                    <span class="fw-semibold text-white">{{ $alumni->graduation_year ?: ($alumni->batch?->passing_year ?: '—') }}</span>
                  </div>
                </div>

                <div class="row g-2">
                  <div class="col-6">
                    <span class="text-secondary d-block" style="font-size: 0.75rem;">SESSION</span>
                    <span class="fw-semibold text-white">{{ $alumni->session ?: ($alumni->batch?->session ?: '—') }}</span>
                  </div>
                  @if ($alumni->blood_group)
                    <div class="col-6">
                      <span class="text-secondary d-block" style="font-size: 0.75rem;">BLOOD GROUP</span>
                      <span class="fw-bold text-danger">{{ $alumni->blood_group }}</span>
                    </div>
                  @endif
                </div>
              </div>

              {{-- Right Column: Live QR Code --}}
              <div class="col-sm-3 text-center">
                <div class="alumni-qr-box p-2 bg-white rounded-3 shadow-sm d-inline-block mb-2 text-center" style="background-color: #ffffff !important;">
                  <img
                    src="{{ $alumni->qrCodeDataUri() }}"
                    alt="Alumni Verification QR Code"
                    width="180"
                    height="180"
                    class="img-fluid d-block mx-auto rounded-2"
                    style="width: 180px; height: 180px; min-width: 180px; min-height: 180px; object-fit: contain; image-rendering: pixelated; background: #ffffff;"
                  >
                </div>
                <div class="small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.08em; color: #c89b3c;">
                  Scan to Verify
                </div>
                <div class="text-secondary" style="font-size: 0.65rem;">
                  Official Cryptographic Token
                </div>
              </div>

            </div>
          </div>

          {{-- Card Footer --}}
          <div class="px-4 py-2 bg-black bg-opacity-30 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center small text-secondary" style="font-size: 0.72rem;">
            <span>Bangladesh University of Professionals &bull; Mirpur Cantonment, Dhaka</span>
            <span>Verified: {{ $alumni->verified_at?->format('M Y') ?? 'Active' }}</span>
          </div>

        </div>

      </div>
    </div>

    {{-- Explanatory Panel --}}
    <div class="card-feature h-auto mt-4 p-4">
      <h3 class="h6 mb-2">About Your Digital Alumni ID</h3>
      <p class="small text-muted-slate mb-2">
        This Digital Alumni ID is your official electronic credential as a graduate of the Department of Information and Communication Technology, Bangladesh University of Professionals.
      </p>
      <ul class="small text-muted-slate mb-0 ps-3">
        <li><strong>Unique Alumni ID:</strong> Formatted as <code>BUPICT-AL-XXXXXX</code>, this identifier confirms your standing in the association.</li>
        <li><strong>Tamper-Proof QR Code:</strong> Contains a secure token that resolves live against the institution database, without embedding sensitive personal data.</li>
        <li><strong>Verification Rights:</strong> Can be scanned by event staff, university administration, and employers to confirm your graduate authenticity.</li>
      </ul>
    </div>
  @endif
@endsection

@push('styles')
.alumni-qr-box {
  background: #ffffff !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
  border-radius: 8px !important;
}

.alumni-qr-box img {
  display: block !important;
  width: 180px !important;
  height: 180px !important;
  max-width: 100% !important;
  background: #ffffff !important;
}

@media print {
  /* Hide page navigation, sidebar, header, and buttons */
  header, nav, .site-navbar, .sidebar, .sidebar-alumni, .sidebar-admin, .btn, .d-flex.justify-content-between.align-items-center, .card-feature.mt-4, footer {
    display: none !important;
  }

  body, html, main {
    background: #fff !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .content-area, .dashboard-container, .container-fluid {
    padding: 0 !important;
    margin: 0 !important;
  }

  #printable-alumni-card {
    border: 2px solid #000 !important;
    box-shadow: none !important;
    margin: 20px auto !important;
    width: 100% !important;
    max-width: 650px !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .alumni-qr-box {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
}
</style>
@endpush
