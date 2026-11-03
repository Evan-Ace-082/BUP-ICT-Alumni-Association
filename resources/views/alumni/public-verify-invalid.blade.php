@extends('layouts.app')

@section('title', 'Invalid Verification Token — BUP ICT Alumni Association')

@section('content')
<div class="py-5 bg-light min-vh-75 d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">

        <div class="card border-0 shadow-sm rounded-4 text-center p-4 p-md-5 bg-white">
          <div class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; font-size: 32px;">
            !
          </div>

          <h1 class="h4 fw-bold text-dark mb-2">Invalid Verification Token</h1>

          <p class="text-muted-slate small mb-4">
            The scanned QR Code or verification token could not be found in the official registry of the BUP ICT Alumni Association. It may be expired, invalid, or issued by an unauthorized party.
          </p>

          <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('home') }}" class="btn btn-sm btn-brass">
              Return to Association Homepage
            </a>
            <a href="{{ route('contact') }}" class="btn btn-sm btn-outline-ink">
              Contact Department
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
