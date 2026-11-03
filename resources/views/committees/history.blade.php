@extends('layouts.app')

@section('title', 'Executive Committee History — BUP ICT Alumni Association')

@section('content')
  <div class="py-5 bg-parchment border-bottom">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <span class="badge bg-brass text-dark px-3 py-2 text-uppercase mb-2" style="letter-spacing: .08em; font-size: 11px;">Leadership Archive</span>
          <h1 class="display-6 font-display fw-bold mb-2">Executive Committee History</h1>
          <p class="text-muted-slate mb-0 lead small">
            Honoring the past leaders and executive bodies who built our alumni community.
          </p>
        </div>
        <div>
          <a href="{{ route('committees.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Current Committee</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">
    @if($committees->isEmpty())
      <div class="card-feature text-center py-5">
        <h2 class="h5 font-display mb-2">No historical committees on record</h2>
        <p class="small text-muted-slate mb-0">Previous committee records will be preserved here when leadership transitions occur.</p>
      </div>
    @else
      <div class="row g-4">
        @foreach($committees as $committee)
          <div class="col-md-6 col-lg-4">
            <div class="card-feature h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-secondary">Term: {{ $committee->term }}</span>
                  <span class="small text-muted-slate">{{ $committee->members->count() }} Members</span>
                </div>

                <h3 class="h6 font-display mb-2">
                  <a href="{{ route('committees.show', $committee) }}" class="text-decoration-none">
                    Executive Committee ({{ $committee->term }})
                  </a>
                </h3>

                <p class="small text-muted-slate mb-3">
                  {{ $committee->election ? "Origin: Elected in {$committee->election->title}" : 'Origin: Appointed Leadership' }}
                </p>
              </div>

              <div>
                <a href="{{ route('committees.show', $committee) }}" class="btn btn-outline-ink btn-sm w-100">
                  View Full Committee Roster →
                </a>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-4">
        {{ $committees->links() }}
      </div>
    @endif
  </div>
@endsection
