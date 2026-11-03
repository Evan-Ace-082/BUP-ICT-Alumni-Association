@extends('layouts.app')

@section('title', 'About — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">About</div>
      <h1 class="mb-3">The Association, and why it exists</h1>
      <p class="lead mb-0">Connecting Generations, Building the Future.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-8">
          <h2 class="h4 mb-3">Our purpose</h2>
          <p class="text-muted-slate">
            The BUP ICT Alumni Association is the permanent, official community for graduates
            of the Department of ICT at Bangladesh University of Professionals. It exists to
            keep every batch — from the earliest graduates to those still to come — connected
            to each other and to the department.
          </p>
          <h2 class="h4 mb-3 mt-4">Where we are today</h2>
          <p class="text-muted-slate">
            This platform currently covers Batch 01 through Batch 08, with more batches
            joining as the department continues to graduate new cohorts. The system is built
            so future batches can be added without any change to the underlying architecture.
          </p>
          <h2 class="h4 mb-3 mt-4">What's ahead</h2>
          <p class="text-muted-slate">
            Future phases will bring a full alumni directory, mentorship and career features,
            events and galleries, association elections, and more — all built on the
            foundation established in this phase.
          </p>
        </div>
        <div class="col-lg-4">
          <div class="card-feature">
            <div class="icon-badge">BUP</div>
            <h3 class="h6">Bangladesh University of Professionals</h3>
            <p class="small text-muted-slate mb-0">Department of ICT</p>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
