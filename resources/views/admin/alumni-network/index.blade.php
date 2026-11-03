@extends('layouts.dashboard')

@section('title', 'Alumni Network Management — BUP ICT Alumni Association')
@section('page_title', 'Alumni Network Management')


@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  <!-- Category Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card-feature stat-block text-center p-3">
        <div class="stat-number fs-3 fw-bold">{{ $abroadCount }}</div>
        <div class="stat-label small text-muted-slate">🌍 Alumni Abroad</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature stat-block text-center p-3">
        <div class="stat-number fs-3 fw-bold">{{ $professionalsCount }}</div>
        <div class="stat-label small text-muted-slate">🏢 Professionals in BD</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature stat-block text-center p-3">
        <div class="stat-number fs-3 fw-bold">{{ $entrepreneursCount }}</div>
        <div class="stat-label small text-muted-slate">🚀 Entrepreneurs</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature stat-block text-center p-3">
        <div class="stat-number fs-3 fw-bold">{{ $higherStudiesCount }}</div>
        <div class="stat-label small text-muted-slate">🎓 Higher Studies</div>
      </div>
    </div>
  </div>

  <!-- Actions Bar -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="h6 mb-0">Manage Network Profiles</h2>
      <p class="small text-muted-slate mb-0">Update location, employment, higher studies, and public discovery fields.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('alumni.network') }}" target="_blank" class="btn btn-sm btn-outline-ink">
        🌐 View Public Network
      </a>
      <a href="{{ route('admin.alumni-network.create') }}" class="btn btn-sm btn-brass">
        + Add Alumni Information
      </a>
    </div>
  </div>

  <!-- Filters Panel -->
  <div class="card-feature mb-4">
    <form method="GET" action="{{ route('admin.alumni-network.index') }}" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small" for="search">Search</label>
        <input
          type="text"
          name="search"
          id="search"
          class="form-control form-control-sm"
          placeholder="Name or Student ID"
          value="{{ $filters['search'] ?? '' }}"
        >
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="batch_id">Batch</label>
        <select name="batch_id" id="batch_id" class="form-select form-select-sm">
          <option value="">All batches</option>
          @foreach ($batches as $batch)
            <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? null) == $batch->id)>
              {{ $batch->batch_name }} ({{ $batch->batch_code }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="category">Network Category</label>
        <select name="category" id="category" class="form-select form-select-sm">
          <option value="">All categories</option>
          <option value="abroad" @selected(($filters['category'] ?? '') === 'abroad')>Alumni Abroad</option>
          <option value="professionals" @selected(($filters['category'] ?? '') === 'professionals')>Professionals in BD</option>
          <option value="entrepreneurs" @selected(($filters['category'] ?? '') === 'entrepreneurs')>Entrepreneurs</option>
          <option value="higher_studies" @selected(($filters['category'] ?? '') === 'higher_studies')>Higher Studies & Research</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="country">Country</label>
        <input
          type="text"
          name="country"
          id="country"
          class="form-control form-control-sm"
          placeholder="e.g. Germany"
          value="{{ $filters['country'] ?? '' }}"
        >
      </div>

      <div class="col-md-3">
        <label class="form-label small" for="company">Company</label>
        <input
          type="text"
          name="company"
          id="company"
          class="form-control form-control-sm"
          placeholder="e.g. Google"
          value="{{ $filters['company'] ?? '' }}"
        >
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="city">City</label>
        <input
          type="text"
          name="city"
          id="city"
          class="form-control form-control-sm"
          placeholder="e.g. Berlin"
          value="{{ $filters['city'] ?? '' }}"
        >
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="job_status">Job Status</label>
        <select name="job_status" id="job_status" class="form-select form-select-sm">
          <option value="">All statuses</option>
          @foreach (\App\Models\Alumni::JOB_STATUSES as $status)
            <option value="{{ $status }}" @selected(($filters['job_status'] ?? '') === $status)>{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small" for="designation">Designation</label>
        <input
          type="text"
          name="designation"
          id="designation"
          class="form-control form-control-sm"
          placeholder="e.g. Engineer"
          value="{{ $filters['designation'] ?? '' }}"
        >
      </div>

      <div class="col-md-3">
        <label class="form-label small" for="higher_studies">Studies / Institution</label>
        <input
          type="text"
          name="higher_studies"
          id="higher_studies"
          class="form-control form-control-sm"
          placeholder="e.g. Oxford, PhD"
          value="{{ $filters['higher_studies'] ?? '' }}"
        >
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-brass w-100">Filter</button>
        @if (array_filter($filters))
          <a href="{{ route('admin.alumni-network.index') }}" class="btn btn-sm btn-outline-ink">Clear</a>
        @endif
      </div>
    </form>
  </div>

  <!-- Alumni List Table -->
  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">
        Alumni Records
        <span class="text-muted-slate small fw-normal">— {{ $alumni->total() }} total</span>
      </h2>
    </div>

    @if ($alumni->isEmpty())
      <div class="placeholder-panel py-4 text-center">
        @if (array_filter($filters))
          <h3 class="h6 mb-2">No matching alumni records</h3>
          <p class="mb-0 text-muted-slate small">Try adjusting or clearing your search filters above.</p>
        @else
          <h3 class="h6 mb-2">No alumni records found</h3>
          <p class="mb-0 text-muted-slate small">Add alumni information or import from Excel to populate records.</p>
        @endif
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-sm small align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 110px;">Student ID</th>
              <th>Alumnus</th>
              <th>Batch</th>
              <th>Location</th>
              <th>Employment / Higher Studies</th>
              <th>Network Categories</th>
              <th class="text-end" style="width: 140px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($alumni as $alumnus)
              <tr>
                <td class="font-mono text-muted-slate">{{ $alumnus->student_id }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded border overflow-hidden flex-shrink-0" style="width: 32px; height: 32px; background-color: var(--parchment-50, #f8f6f1);">
                      @if ($alumnus->profile_photo_url)
                        <img src="{{ $alumnus->profile_photo_url }}" alt="{{ $alumnus->full_name }}" class="w-100 h-100 object-fit-cover">
                      @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center fw-bold text-muted-slate" style="font-size: 11px;">
                          {{ substr($alumnus->full_name, 0, 1) }}
                        </div>
                      @endif
                    </div>
                    <div>
                      <div class="fw-semibold">{{ $alumnus->full_name }}</div>
                      @if ($alumnus->graduation_year)
                        <div class="text-muted-slate" style="font-size: 11px;">Class of {{ $alumnus->graduation_year }}</div>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <span>{{ $alumnus->batch?->batch_code ?? '—' }}</span>
                </td>
                <td>
                  @if ($alumnus->city || $alumnus->country)
                    <div>📍 {{ collect([$alumnus->city, $alumnus->country])->filter()->implode(', ') }}</div>
                  @else
                    <span class="text-muted-slate">—</span>
                  @endif
                </td>
                <td>
                  @if ($alumnus->designation || $alumnus->company)
                    <div>
                      <span class="fw-semibold">{{ $alumnus->designation ?: 'Staff' }}</span>
                      @if ($alumnus->company)
                        <span class="text-muted-slate">at {{ $alumnus->company }}</span>
                      @endif
                    </div>
                  @elseif ($alumnus->current_institution || $alumnus->higher_studies)
                    <div>
                      <span>{{ $alumnus->higher_studies ?: $alumnus->current_institution }}</span>
                    </div>
                  @else
                    <span class="text-muted-slate">{{ $alumnus->job_status ?: '—' }}</span>
                  @endif
                </td>
                <td>
                  @php($cats = $alumnus->getCategories())
                  @if (empty($cats))
                    <span class="badge bg-light text-muted-slate border">Uncategorized</span>
                  @else
                    @foreach ($cats as $cat)
                      @if ($cat === 'Abroad')
                        <span class="badge bg-parchment text-brass border">🌍 Abroad</span>
                      @elseif ($cat === 'Bangladesh Professional')
                        <span class="badge bg-light text-dark border">🏢 Professional BD</span>
                      @elseif ($cat === 'Entrepreneur')
                        <span class="badge bg-brass text-white">🚀 Entrepreneur</span>
                      @elseif ($cat === 'Higher Studies & Research')
                        <span class="badge bg-parchment text-ink border">🎓 Higher Studies</span>
                      @endif
                    @endforeach
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <a href="{{ route('admin.alumni-network.edit', $alumnus) }}" class="btn btn-xs btn-outline-ink px-2 py-1" style="font-size: 12px;">
                      Edit
                    </a>
                    <a href="{{ route('alumni.show', $alumnus) }}" target="_blank" class="btn btn-xs btn-outline-ink px-2 py-1 text-decoration-none" style="font-size: 12px;" title="View public profile">
                      ↗
                    </a>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $alumni->links() }}
      </div>
    @endif
  </div>
@endsection
