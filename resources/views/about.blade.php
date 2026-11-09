@extends('layouts.app')

@section('title', 'About — BUP ICT Alumni Association')

@section('content')
  {{-- Compact Hero Header --}}
  <section class="hero hero-compact">
    <div class="container">
      <div class="eyebrow mb-2">About</div>
      <h1 class="mb-1">The Association, and why it exists</h1>
      <p class="text-muted-glass small mb-0">Connecting Generations &middot; Building the Future of Technology and Leadership.</p>
    </div>
  </section>

  <section class="section py-4">
    <div class="container">
      <div class="row gy-4">
        {{-- Left Column: Structured Story & Milestones --}}
        <div class="col-lg-8">
          <div class="card-feature mb-4">
            <span class="section-eyebrow mb-2">Our Mission</span>
            <h2 class="h4 mb-3">Connecting graduates with lasting departmental impact</h2>
            <p class="text-muted-slate mb-3">
              The BUP ICT Alumni Association is the permanent, official body representing graduates
              of the Department of Information and Communication Technology at Bangladesh University
              of Professionals. It bridges our earliest pioneer cohorts with current graduating students,
              fostering technical collaboration, mentorship, industry placements, and institutional progress.
            </p>
            <p class="text-muted-slate mb-0">
              Anchored on a batch-first architecture, every graduate maintains a persistent connection
              to their academic roots and fellow cohort members regardless of where their global careers take them.
            </p>
          </div>

          <div class="card-feature">
            <span class="section-eyebrow mb-3">Institutional Milestones</span>
            <div class="timeline-stepper">
              <div class="timeline-step pb-3 border-start ps-3 position-relative">
                <span class="timeline-marker"></span>
                <span class="font-mono small text-brass fw-semibold">2015</span>
                <h3 class="h6 mb-1 fw-bold">Founding of Department of ICT</h3>
                <p class="small text-muted-slate mb-0">Initiation of the undergraduate curriculum under the Faculty of Science and Technology at BUP.</p>
              </div>

              <div class="timeline-step pb-3 border-start ps-3 position-relative">
                <span class="timeline-marker"></span>
                <span class="font-mono small text-brass fw-semibold">2019</span>
                <h3 class="h6 mb-1 fw-bold">First Graduating Cohort (Batch 01)</h3>
                <p class="small text-muted-slate mb-0">Pioneer graduates transition into national engineering, telecommunications, and software sectors.</p>
              </div>

              <div class="timeline-step ps-3 border-start-0 position-relative">
                <span class="timeline-marker"></span>
                <span class="font-mono small text-brass fw-semibold">2026 & Beyond</span>
                <h3 class="h6 mb-1 fw-bold">Unified Digital Alumni Ecosystem</h3>
                <p class="small text-muted-slate mb-0">Launch of official digitized credentialing, directory search, mentorship programs, and democratic executive governance.</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Right Column: Institutional Profile & Key Facts --}}
        <div class="col-lg-4">
          <div class="card-feature mb-3">
            <div class="d-flex align-items-center gap-3 mb-3">
              <img src="{{ asset('images/bup-ict-logo.jpg') }}" alt="BUP ICT Emblem" class="brand-logo" style="width: 52px; height: 52px;">
              <div>
                <h3 class="h6 fw-bold mb-0">Department of ICT</h3>
                <small class="text-muted-slate font-mono">BUP &middot; FST</small>
              </div>
            </div>
            
            <p class="small text-muted-slate mb-3">Faculty of Science and Technology, Bangladesh University of Professionals.</p>
            
            <ul class="list-unstyled small border-top pt-3 mb-0">
              <li class="d-flex justify-content-between py-1">
                <span class="text-muted-slate">Campus:</span>
                <span class="fw-semibold">Mirpur Cantonment</span>
              </li>
              <li class="d-flex justify-content-between py-1">
                <span class="text-muted-slate">Current Batches:</span>
                <span class="fw-semibold font-mono">Batch 01 &ndash; 08+</span>
              </li>
              <li class="d-flex justify-content-between py-1">
                <span class="text-muted-slate">Association Status:</span>
                <span class="badge bg-success-subtle text-success">Official Chapter</span>
              </li>
            </ul>
          </div>

          <div class="card-feature bg-parchment">
            <h3 class="h6 fw-semibold mb-2">Association Governance</h3>
            <p class="small text-muted-slate mb-3">Administered under democratic election cycles and executive committee supervision.</p>
            <a href="{{ route('committees.index') }}" class="btn btn-outline-ink btn-sm w-100">View Executive Committee</a>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
