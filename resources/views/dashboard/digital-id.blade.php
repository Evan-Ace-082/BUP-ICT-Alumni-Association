@extends('layouts.dashboard')

@section('title', 'My Digital Alumni ID — BUP ICT Alumni Association')
@section('page_title', 'My Digital Alumni ID')

@section('content')
  @if (! $alumni)
    {{-- No Linked Profile State --}}
    <div class="card-feature h-auto text-center p-5">
      <div class="fs-1 text-muted mb-2">🎓</div>
      <h2 class="h5 mb-2">No Alumni Profile Linked</h2>
      <p class="small text-muted-slate mb-4">Your account does not have a linked official alumni record.</p>
      <a href="{{ route('dashboard.profile.edit') }}" class="btn btn-sm btn-brass">
        Create or Link Alumni Profile
      </a>
    </div>

  @elseif ($isPending)
    {{-- Improved Pending Verification State --}}
    <div class="card-feature h-auto p-4 p-md-5 mb-4">
      <div class="text-center mb-4">
        {{-- Professional Verification Status Icon --}}
        <div class="verification-pending-icon-wrapper mb-3">
          <div class="verification-pulse-ring"></div>
          <div class="verification-icon-circle bg-warning text-dark shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
              <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
            </svg>
          </div>
        </div>

        <div class="d-inline-block mb-2">
          <span class="badge bg-warning text-dark px-3 py-2 fw-semibold fs-6 rounded-pill">
            ⏳ Under Administrator Review
          </span>
        </div>

        <h2 class="h4 fw-bold text-ink-900 mb-2">Alumni Verification Pending</h2>
        <p class="text-muted-slate small mb-4 mx-auto" style="max-width: 580px;">
          Your email has been verified successfully. Your alumni verification is currently pending admin approval. Once an administrator verifies your graduation records, your official Digital Alumni ID and QR Code will be generated here automatically.
        </p>
      </div>

      {{-- 3-Step Verification Progress Bar --}}
      <div class="verification-stepper mx-auto mb-5" style="max-width: 620px;">
        <div class="row g-2 text-center">
          <div class="col-4">
            <div class="stepper-item completed">
              <div class="stepper-icon bg-success text-white">✓</div>
              <div class="stepper-label fw-semibold text-ink-900 small mt-2">Email Verified</div>
              <div class="text-muted-slate" style="font-size: 11px;">Completed</div>
            </div>
          </div>
          <div class="col-4">
            <div class="stepper-item active">
              <div class="stepper-icon bg-warning text-dark fw-bold">⏳</div>
              <div class="stepper-label fw-semibold text-ink-900 small mt-2">Admin Review</div>
              <div class="text-muted-slate" style="font-size: 11px;">In Progress</div>
            </div>
          </div>
          <div class="col-4">
            <div class="stepper-item locked">
              <div class="stepper-icon bg-light text-muted border">🔒</div>
              <div class="stepper-label fw-semibold text-muted small mt-2">Digital ID &amp; QR</div>
              <div class="text-muted-slate" style="font-size: 11px;">Upon Approval</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Submitted Profile Review Card --}}
      <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
          <div class="border rounded-3 p-4 bg-light shadow-sm">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
              <div class="fw-bold text-ink-900 small text-uppercase" style="letter-spacing: 0.05em;">
                📋 Submitted Profile Information
              </div>
              @if ($alumni->created_at)
                <span class="text-muted-slate small" style="font-size: 12px;">
                  Submitted on {{ $alumni->created_at->format('M d, Y, h:i A') }}
                </span>
              @endif
            </div>

            <div class="row g-3 small">
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">FULL NAME</span>
                <span class="fw-semibold text-ink-900 fs-6">{{ $alumni->full_name }}</span>
              </div>
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">STUDENT ID</span>
                <span class="font-monospace fw-bold text-ink-900">{{ $alumni->student_id }}</span>
              </div>
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">OFFICIAL BATCH</span>
                <span class="fw-semibold text-ink-900">{{ $alumni->batch?->batch_name ?? $alumni->batch?->batch_code ?? '—' }}</span>
              </div>
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">PASSING / GRADUATION YEAR</span>
                <span class="fw-semibold text-ink-900">{{ $alumni->graduation_year ?: ($alumni->batch?->graduation_year ?: '—') }}</span>
              </div>
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">REGISTERED EMAIL</span>
                <span class="fw-semibold text-ink-900">{{ $user->email }}</span>
              </div>
              <div class="col-sm-6">
                <span class="text-muted-slate d-block" style="font-size: 11px;">CONTACT PHONE</span>
                <span class="fw-semibold text-ink-900">{{ $alumni->phone ?: ($user->phone ?: 'Not provided') }}</span>
              </div>
              @if ($alumni->session || $alumni->batch?->session)
                <div class="col-sm-6">
                  <span class="text-muted-slate d-block" style="font-size: 11px;">ACADEMIC SESSION</span>
                  <span class="fw-semibold text-ink-900">{{ $alumni->session ?: $alumni->batch?->session }}</span>
                </div>
              @endif
            </div>

            <div class="mt-3 pt-3 border-top small text-muted-slate">
              <div class="d-flex align-items-start gap-2">
                <span class="text-brass fs-6">&#9432;</span>
                <div>
                  <strong>Administrative Verification Policy:</strong> To maintain the authenticity of the BUP ICT Alumni network, each registration is cross-referenced with official university registrar and department records. You will receive an automated email as soon as an administrator verifies your account.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="text-center d-flex justify-content-center gap-2 flex-wrap">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-ink">
          &larr; Return to Dashboard
        </a>
        <a href="{{ route('dashboard.profile.edit') }}" class="btn btn-sm btn-brass">
          Edit Profile Information
        </a>
      </div>
    </div>

  @elseif ($isRejected)
    {{-- Rejected State --}}
    <div class="card-feature h-auto text-center p-4 p-md-5 mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle mb-3 shadow" style="width: 72px; height: 72px; font-size: 36px;">
        ✕
      </div>
      <h2 class="h4 fw-bold text-danger mb-2">Alumni Verification Not Approved</h2>
      <p class="text-muted-slate small mb-3 mx-auto" style="max-width: 540px;">
        Your alumni verification request was reviewed and could not be approved at this time.
      </p>

      @if ($alumni->rejection_reason)
        <div class="alert alert-danger mx-auto text-start small mb-4 shadow-sm" style="max-width: 500px;">
          <strong>Admin Remarks:</strong> {{ $alumni->rejection_reason }}
        </div>
      @endif

      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <a href="{{ route('dashboard.profile.edit') }}" class="btn btn-sm btn-brass">
          Review Profile Information
        </a>
        <a href="{{ route('contact') }}" class="btn btn-sm btn-outline-ink">
          Contact Support
        </a>
      </div>
    </div>

  @else
    {{-- Approved / Verified Digital ID Card --}}
    <div class="digital-id-actions d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <div>
        <h2 class="h6 mb-0 font-display">Official Digital Membership Card</h2>
        <span class="small text-muted-slate">Issued by Department of Information &amp; Communication Technology, BUP</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" id="btnDownloadCard" class="btn btn-sm btn-brass d-inline-flex align-items-center gap-1 shadow-sm" onclick="downloadDigitalId()">
          <span>📥</span>
          <span>Download Digital ID</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-ink d-inline-flex align-items-center gap-1" onclick="window.print()">
          <span>🖨️</span>
          <span>Print Digital Card</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-ink d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#qrModal">
          <span>🔍</span>
          <span>View QR Code</span>
        </button>
        @if ($alumni->qr_token)
          <a href="{{ $alumni->qrVerificationUrl() }}" target="_blank" class="btn btn-sm btn-ghost-sm d-inline-flex align-items-center gap-1">
            <span>🔗</span>
            <span>Verify URL</span>
          </a>
        @endif
      </div>
    </div>

    {{-- The Digital ID Card Container (Screen & Print-Optimized) --}}
    <div class="row justify-content-center my-3">
      <div class="col-xl-8 col-lg-10">

        <div id="printable-alumni-card" class="alumni-digital-card position-relative text-white rounded-4 shadow-lg overflow-hidden">
          {{-- Decorative Card Watermark Background --}}
          <div class="card-watermark" aria-hidden="true"></div>

          {{-- Card Header --}}
          <div class="card-header-section p-3 p-sm-4 border-bottom border-warning border-opacity-25 d-flex justify-content-between align-items-center position-relative">
            <div class="d-flex align-items-center gap-3">
              <div class="logo-wrapper bg-white p-1 rounded-circle shadow d-flex align-items-center justify-content-center">
                <img src="{{ asset('images/bup-ict-logo.jpg') }}" alt="BUP ICT Logo" class="rounded-circle" style="width: 44px; height: 44px; object-fit: cover;">
              </div>
              <div>
                <div class="card-brand-title text-uppercase font-display fw-bold tracking-wide">
                  BUP ICT Alumni Association
                </div>
                <div class="card-brand-subtitle small">
                  Department of Information and Communication Technology
                </div>
                <div class="card-brand-dept" style="font-size: 11px; opacity: 0.85;">
                  Bangladesh University of Professionals (BUP)
                </div>
              </div>
            </div>
            <div class="text-end">
              <span class="badge rounded-pill text-dark fw-bold px-3 py-2 official-badge">
                OFFICIAL ALUMNI
              </span>
            </div>
          </div>

          {{-- Card Body --}}
          <div class="card-body-section p-3 p-sm-4 position-relative">
            <div class="row g-3 g-sm-4 align-items-center">

              {{-- Left Column: Photo & Digital Alumni ID --}}
              <div class="col-md-4 col-sm-5 text-center">
                <div class="position-relative d-inline-block mb-3">
                  @if ($alumni->profile_photo_url)
                    <img
                      src="{{ $alumni->profile_photo_url }}"
                      alt="{{ $alumni->full_name }}"
                      class="rounded-circle border border-3 border-warning shadow"
                      style="width: 115px; height: 115px; object-fit: cover;"
                    >
                  @else
                    <div
                      class="rounded-circle border border-3 d-flex align-items-center justify-content-center mx-auto shadow avatar-initials"
                      style="width: 115px; height: 115px;"
                    >
                      {{ strtoupper(substr($alumni->full_name, 0, 1)) }}
                    </div>
                  @endif
                  <span class="position-absolute bottom-0 end-0 bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" title="Verified Member" style="width: 26px; height: 26px; font-size: 13px; border: 2px solid #0d1b33;">
                    ✓
                  </span>
                </div>

                <div class="text-uppercase text-muted-gold small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                  Digital Alumni ID
                </div>
                <div class="d-flex align-items-center justify-content-center gap-1 mt-1">
                  <span class="font-monospace fw-bold fs-6 text-gold" id="alumniIdDisplay">{{ $alumni->alumni_id }}</span>
                  <button type="button" class="btn btn-link btn-sm text-gold p-0 ms-1 copy-btn" title="Copy Digital ID" onclick="copyDigitalId('{{ $alumni->alumni_id }}', this)">
                    📋
                  </button>
                </div>
              </div>

              {{-- Middle Column: Graduate Details --}}
              <div class="col-md-5 col-sm-7">
                <h3 class="h5 fw-bold mb-2 text-white font-display">{{ $alumni->full_name }}</h3>

                <div class="small mb-2">
                  <span class="text-muted-gold d-block" style="font-size: 0.7rem; letter-spacing: 0.05em;">STUDENT ID</span>
                  <span class="font-monospace fw-bold fs-6 text-white">{{ $alumni->student_id }}</span>
                </div>

                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <span class="text-muted-gold d-block" style="font-size: 0.7rem;">BATCH</span>
                    <span class="fw-semibold text-white">{{ $alumni->batch?->batch_name ?? $alumni->batch?->batch_code ?? '—' }}</span>
                  </div>
                  <div class="col-6">
                    <span class="text-muted-gold d-block" style="font-size: 0.7rem;">PASSING YEAR</span>
                    <span class="fw-semibold text-white">{{ $alumni->graduation_year ?: ($alumni->batch?->graduation_year ?: ($alumni->batch?->passing_year ?: '—')) }}</span>
                  </div>
                </div>

                <div class="row g-2">
                  @if ($alumni->session || $alumni->batch?->session)
                    <div class="col-6">
                      <span class="text-muted-gold d-block" style="font-size: 0.7rem;">SESSION</span>
                      <span class="fw-semibold text-white">{{ $alumni->session ?: $alumni->batch?->session }}</span>
                    </div>
                  @endif
                  @if ($alumni->blood_group)
                    <div class="col-6">
                      <span class="text-muted-gold d-block" style="font-size: 0.7rem;">BLOOD GROUP</span>
                      <span class="fw-bold text-danger">{{ $alumni->blood_group }}</span>
                    </div>
                  @endif
                </div>

                @if ($alumni->designation || $alumni->company || $alumni->current_institution)
                  <div class="mt-2 pt-2 border-top border-white border-opacity-10 small">
                    <span class="text-muted-gold d-block" style="font-size: 0.7rem;">CURRENT AFFILIATION</span>
                    <span class="text-white-50">
                      {{ $alumni->designation ?: 'Professional' }}
                      @if ($alumni->company || $alumni->current_institution)
                        &bull; {{ $alumni->company ?: $alumni->current_institution }}
                      @endif
                    </span>
                  </div>
                @endif
              </div>

              {{-- Right Column: Live QR Code Box --}}
              <div class="col-md-3 col-sm-12 text-center mt-3 mt-md-0">
                <div class="alumni-qr-box p-2 bg-white rounded-3 shadow-sm d-inline-block mb-1 text-center">
                  <img
                    src="{{ $alumni->qrCodeDataUri() }}"
                    alt="Alumni Verification QR Code"
                    width="140"
                    height="140"
                    class="img-fluid d-block mx-auto rounded-2"
                    style="width: 140px; height: 140px; object-fit: contain; image-rendering: pixelated; background: #ffffff;"
                  >
                </div>
                <div class="text-uppercase fw-semibold scan-to-verify-text" style="font-size: 0.7rem; letter-spacing: 0.08em; color: #d6a04b;">
                  Scan to Verify
                </div>
                <div class="text-white-50" style="font-size: 0.65rem;">
                  Official Cryptographic Token
                </div>
              </div>

            </div>
          </div>

          {{-- Card Footer --}}
          <div class="card-footer-section px-4 py-2 border-top border-warning border-opacity-25 d-flex justify-content-between align-items-center small" style="font-size: 0.72rem;">
            <span class="text-white-50">Bangladesh University of Professionals &bull; Mirpur Cantonment, Dhaka</span>
            <span class="text-gold fw-semibold">Verified: {{ $alumni->verified_at?->format('M Y') ?? 'Active' }}</span>
          </div>

        </div>

      </div>
    </div>

    {{-- Explanatory Panel --}}
    <div class="explanatory-panel card-feature h-auto mt-4 p-4 shadow-sm">
      <h3 class="h6 mb-2 font-display">About Your Digital Alumni ID</h3>
      <p class="small text-muted-slate mb-3">
        This Digital Alumni ID is your official electronic credential as a graduate of the Department of Information and Communication Technology, Bangladesh University of Professionals.
      </p>
      <div class="row g-3 small">
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-light">
            <div class="fw-bold text-ink-900 mb-1">🎓 Unique Membership ID</div>
            <p class="text-muted-slate mb-0" style="font-size: 12px;">
              Formatted as <code>{{ $alumni->alumni_id }}</code>, confirming your recognized standing in the official alumni registry.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-light">
            <div class="fw-bold text-ink-900 mb-1">🔒 Tamper-Proof QR Code</div>
            <p class="text-muted-slate mb-0" style="font-size: 12px;">
              Cryptographically signed token that validates live against the university database without exposing private contact info.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-light">
            <div class="fw-bold text-ink-900 mb-1">🛡️ Instant Verification</div>
            <p class="text-muted-slate mb-0" style="font-size: 12px;">
              Can be scanned by event authorities, university admins, or employers to confirm authentic graduate credentials.
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- View QR Code Modal --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0 overflow-hidden">
          <div class="modal-header text-white" style="background: linear-gradient(135deg, #0e1f38 0%, #152542 100%); border-bottom: 2px solid #d6a04b;">
            <div class="d-flex align-items-center gap-2">
              <span class="fs-5">🔍</span>
              <h5 class="modal-title font-display h6 mb-0" id="qrModalLabel">Alumni Verification QR Code</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center p-4">
            <div class="p-3 bg-white d-inline-block rounded-3 shadow-sm border mb-3">
              <img
                src="{{ $alumni->qrCodeDataUri() }}"
                alt="Alumni Verification QR Code"
                width="200"
                height="200"
                class="img-fluid"
                style="image-rendering: pixelated;"
              >
            </div>
            <h5 class="fw-bold text-ink-900 mb-1">{{ $alumni->full_name }}</h5>
            <div class="font-monospace fw-bold text-brass mb-3">{{ $alumni->alumni_id }}</div>
            <p class="small text-muted-slate mb-3">
              Scan this QR code with any smartphone camera to view the authentic public verification page.
            </p>

            @if ($alumni->qr_token)
              <div class="input-group input-group-sm mb-3">
                <input type="text" class="form-control font-monospace" value="{{ $alumni->qrVerificationUrl() }}" id="qrUrlInput" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('qrUrlInput').value); alert('Verification link copied to clipboard!');">
                  Copy Link
                </button>
              </div>
            @endif
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-sm btn-outline-ink" data-bs-dismiss="modal">Close</button>
            @if ($alumni->qr_token)
              <a href="{{ $alumni->qrVerificationUrl() }}" target="_blank" class="btn btn-sm btn-brass">
                Open Verification Page &rarr;
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@push('styles')
<style>
/* ==========================================================================
   Screen Styles for Digital Alumni ID
   ========================================================================== */

.alumni-digital-card {
  background: linear-gradient(135deg, #091426 0%, #152747 50%, #0d1b33 100%) !important;
  border: 2px solid #d6a04b !important;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35) !important;
  min-height: 280px;
}

.card-watermark {
  position: absolute;
  top: 50%;
  right: -20px;
  transform: translateY(-50%);
  width: 280px;
  height: 280px;
  background: radial-gradient(circle, rgba(214, 160, 75, 0.08) 0%, rgba(214, 160, 75, 0) 70%);
  pointer-events: none;
  border-radius: 50%;
}

.card-header-section {
  background: rgba(0, 0, 0, 0.25);
}

.card-footer-section {
  background: rgba(0, 0, 0, 0.4);
}

.card-brand-title {
  letter-spacing: 0.06em;
  color: #ffffff;
  font-size: 0.95rem;
}

.card-brand-subtitle {
  color: #d6a04b;
  font-weight: 500;
  font-size: 0.78rem;
}

.official-badge {
  background-color: #d6a04b !important;
  color: #0b172a !important;
  letter-spacing: 0.06em;
  font-size: 0.75rem;
}

.text-gold {
  color: #f0c36d !important;
}

.text-muted-gold {
  color: #d6a04b !important;
  opacity: 0.9;
}

.avatar-initials {
  background-color: #172a4c;
  border-color: #d6a04b !important;
  color: #f0c36d;
  font-size: 40px;
  font-weight: bold;
}

.alumni-qr-box {
  background: #ffffff !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
  border-radius: 8px !important;
  border: 1px solid #e5e7eb;
}

.alumni-qr-box img {
  display: block !important;
  width: 140px !important;
  height: 140px !important;
  max-width: 100% !important;
  background: #ffffff !important;
}

/* Pending State Animations & Stepper */
.verification-pending-icon-wrapper {
  position: relative;
  display: inline-block;
  width: 76px;
  height: 76px;
}

.verification-icon-circle {
  position: relative;
  z-index: 2;
  width: 76px;
  height: 76px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid #ffffff;
}

.verification-pulse-ring {
  position: absolute;
  top: -6px;
  left: -6px;
  right: -6px;
  bottom: -6px;
  border-radius: 50%;
  border: 2px solid #d6a04b;
  animation: pulseRing 2.4s infinite ease-in-out;
}

@keyframes pulseRing {
  0% { transform: scale(0.92); opacity: 0.8; }
  50% { transform: scale(1.08); opacity: 0.3; }
  100% { transform: scale(0.92); opacity: 0.8; }
}

.stepper-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
}

/* ==========================================================================
   Print Styles: Print ONLY the Digital Alumni ID Card
   ========================================================================== */
@media print {
  @page {
    size: auto;
    margin: 10mm;
  }

  body, html {
    background: #ffffff !important;
    color: #000000 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Hide all dashboard shell chrome, sidebar, header, navigation, and buttons */
  .dashboard-sidebar,
  .dashboard-sidebar-backdrop,
  .dashboard-header,
  header,
  nav,
  .site-navbar,
  .btn,
  .btn-group,
  .copy-btn,
  .digital-id-actions,
  .explanatory-panel,
  .verification-stepper,
  footer,
  .modal,
  .modal-backdrop,
  .alert {
    display: none !important;
  }

  .dashboard-shell,
  .dashboard-main,
  .content-area,
  .container-fluid,
  .row,
  .col-xl-8,
  .col-lg-10 {
    display: block !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    border: none !important;
    box-shadow: none !important;
  }

  #printable-alumni-card {
    display: block !important;
    margin: 20px auto !important;
    width: 100% !important;
    max-width: 650px !important;
    border: 2px solid #c89b3c !important;
    border-radius: 16px !important;
    box-shadow: none !important;
    background: linear-gradient(135deg, #091426 0%, #152747 50%, #0d1b33 100%) !important;
    color: #ffffff !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  #printable-alumni-card * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .alumni-qr-box {
    box-shadow: none !important;
    border: 1px solid #d6a04b !important;
    background: #ffffff !important;
  }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function copyDigitalId(idText, buttonEl) {
  if (!idText) return;

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(idText).then(() => {
      showCopyFeedback(buttonEl);
    }).catch(() => {
      fallbackCopy(idText, buttonEl);
    });
  } else {
    fallbackCopy(idText, buttonEl);
  }
}

function fallbackCopy(idText, buttonEl) {
  const tempInput = document.createElement('input');
  tempInput.value = idText;
  document.body.appendChild(tempInput);
  tempInput.select();
  try {
    document.execCommand('copy');
    showCopyFeedback(buttonEl);
  } catch (err) {
    prompt('Copy your Digital Alumni ID:', idText);
  }
  document.body.removeChild(tempInput);
}

function showCopyFeedback(buttonEl) {
  if (!buttonEl) return;
  const originalContent = buttonEl.innerHTML;
  buttonEl.innerHTML = '✓ Copied';
  buttonEl.style.color = '#10b981';
  setTimeout(() => {
    buttonEl.innerHTML = originalContent;
    buttonEl.style.color = '';
  }, 2000);
}

function downloadDigitalId() {
  const cardElement = document.getElementById('printable-alumni-card');
  if (!cardElement) return;

  const btn = document.getElementById('btnDownloadCard');
  const originalHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.innerHTML = '<span>⏳</span><span>Preparing...</span>';
    btn.disabled = true;
  }

  if (typeof html2canvas === 'function') {
    html2canvas(cardElement, {
      scale: 3,
      useCORS: true,
      backgroundColor: null,
      logging: false,
    }).then(canvas => {
      const link = document.createElement('a');
      const filenameId = '{{ $alumni->alumni_id ?? "card" }}';
      link.download = 'BUP-ICT-Alumni-ID-' + filenameId + '.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
      if (btn) {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
      }
    }).catch(err => {
      console.warn('Canvas export fallback to print:', err);
      if (btn) {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
      }
      window.print();
    });
  } else {
    window.print();
  }
}
</script>
@endpush
