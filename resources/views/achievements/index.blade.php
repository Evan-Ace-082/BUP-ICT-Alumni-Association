@extends('layouts.app')

@section('title', 'Achievement Wall — BUP ICT Alumni Association')

@section('content')
  <section class="hero" style="padding: 3.5rem 0 3rem;">
    <div class="container">
      <div class="eyebrow mb-3">Achievement Wall</div>
      <h1 class="mb-2">Celebrating our alumni</h1>
      <p class="lead text-muted mb-0" style="max-width: 680px;">
        Discover remarkable achievements, leadership milestones, research breakthroughs, and professional excellence from our ICT alumni across diverse sectors.
      </p>
    </div>
  </section>

  <section class="section pt-4">
    <div class="container">
      <!-- Search & Filters Bar -->
      <div class="card-feature mb-4 p-4">
        <form method="GET" action="{{ route('achievements.index') }}" class="row g-3 align-items-end">
          <input type="hidden" name="category" value="{{ request('category', 'all') }}">

          <div class="col-lg-5 col-md-6">
            <label class="form-label small fw-semibold text-muted-slate" for="search">Search Achievements</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
              </span>
              <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Title, alumnus name, or organization..." value="{{ request('search') }}">
            </div>
          </div>

          <div class="col-lg-4 col-md-4">
            <label class="form-label small fw-semibold text-muted-slate" for="batch">Batch Filter</label>
            <select name="batch" id="batch" class="form-select">
              <option value="">All Batches</option>
              @foreach ($batches as $batch)
                <option value="{{ $batch->id }}" @selected(request('batch') == $batch->id)>
                  {{ $batch->ict_name ?: $batch->batch_name }} ({{ $batch->batch_code }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-lg-3 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
            @if (request()->hasAny(['search', 'category', 'batch']) && (request('category') !== 'all' || request('search') || request('batch')))
              <a href="{{ route('achievements.index') }}" class="btn btn-outline-secondary" title="Reset all filters">Reset</a>
            @endif
          </div>
        </form>
      </div>

      <!-- Category Filter Pills -->
      <div class="mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="small fw-semibold text-muted-slate me-2">Sectors:</span>
          @php
            $currentCat = request('category', 'all');
          @endphp
          <a href="{{ route('achievements.index', array_merge(request()->except(['page', 'category']), ['category' => 'all'])) }}"
             class="btn btn-sm rounded-pill {{ $currentCat === 'all' || !$currentCat ? 'btn-primary' : 'btn-outline-secondary' }}">
            All Categories
          </a>
          @foreach ($categories as $cat)
            @php
              $isSelected = ($currentCat == $cat->slug || $currentCat == $cat->id);
            @endphp
            <a href="{{ route('achievements.index', array_merge(request()->except(['page', 'category']), ['category' => $cat->slug])) }}"
               class="btn btn-sm rounded-pill {{ $isSelected ? 'btn-primary' : 'btn-outline-secondary' }}">
              {{ $cat->name }}
            </a>
          @endforeach
        </div>
      </div>

      <!-- Achievements Grid -->
      @if ($achievements->isEmpty())
        <div class="placeholder-panel p-5 text-center">
          <div class="mb-3 text-muted">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.774-3.333 3.074v1.5a2.5 2.5 0 0 1 2 2.45v.5a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-.5a2.5 2.5 0 0 1 2-2.45v-1.5c-1.455-.3-2.543-1.21-3.333-3.074A3 3 0 1 1 2.534 2.036 31.815 31.815 0 0 1 2.5.5z"/>
            </svg>
          </div>
          <h2 class="h5 mb-2">No achievements found</h2>
          <p class="text-muted small mb-3">Try adjusting your category, batch, or search terms to find alumni milestones.</p>
          @if (request()->hasAny(['search', 'category', 'batch']))
            <a href="{{ route('achievements.index') }}" class="btn btn-sm btn-outline-primary">View All Achievements</a>
          @endif
        </div>
      @else
        <div class="row g-4">
          @foreach ($achievements as $achievement)
            @php
              $categoryName = $achievement->category->name ?? $achievement->achievement_type ?? 'Achievement';
              $alumnus = $achievement->alumni;
              $batchName = $alumnus?->batch ? ($alumnus->batch->ict_name ?: $alumnus->batch->batch_name) : null;
            @endphp
            <div class="col-md-6 col-lg-4">
              <div class="card-feature h-100 d-flex flex-column border rounded-3 p-0 overflow-hidden shadow-sm">
                @if ($achievement->image)
                  <div style="height: 180px; overflow: hidden; background: #f0f2f5;" class="position-relative">
                    <img src="{{ asset('storage/' . $achievement->image) }}" alt="{{ $achievement->title }}" class="w-100 h-100 object-fit-cover">
                    <span class="badge bg-dark bg-opacity-75 position-absolute top-0 end-0 m-2 px-2 py-1 small">
                      {{ $categoryName }}
                    </span>
                  </div>
                @endif
                <div class="p-3 d-flex flex-column flex-grow-1">
                  @if (! $achievement->image)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="badge bg-light text-primary border small">{{ $categoryName }}</span>
                      @if ($achievement->achievement_date)
                        <span class="small text-muted">{{ $achievement->achievement_date->format('M Y') }}</span>
                      @endif
                    </div>
                  @else
                    @if ($achievement->achievement_date)
                      <div class="small text-muted mb-1">{{ $achievement->achievement_date->format('M Y') }}</div>
                    @endif
                  @endif

                  <h2 class="h6 mb-2 text-dark fw-bold" style="line-height: 1.4;">{{ $achievement->title }}</h2>

                  <p class="small text-muted flex-grow-1 mb-3" style="line-height: 1.6;">
                    {{ \Illuminate\Support\Str::limit($achievement->description, 140) }}
                  </p>

                  <div class="pt-3 border-top mt-auto d-flex align-items-center justify-content-between">
                    <div>
                      <div class="fw-semibold small text-dark">{{ $alumnus->full_name ?? 'ICT Alumnus' }}</div>
                      @if ($batchName)
                        <div class="small text-muted" style="font-size: 0.8rem;">Batch: {{ $batchName }}</div>
                      @endif
                    </div>
                    @if ($achievement->organization)
                      <div class="text-end">
                        <span class="badge bg-secondary-subtle text-secondary small" title="Organization/Institution">
                          {{ \Illuminate\Support\Str::limit($achievement->organization, 22) }}
                        </span>
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
          {{ $achievements->links() }}
        </div>
      @endif
    </div>
  </section>
@endsection
