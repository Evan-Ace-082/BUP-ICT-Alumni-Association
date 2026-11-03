@extends('layouts.dashboard')

@section('title', $election->title . ' — Election Management')
@section('page_title', 'Election Details & Management')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger small mb-4">{{ session('error') }}</div>
  @endif
  @if(isset($errors) && $errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Top Header & Lifecycle Management -->
  <div class="card-feature mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h2 class="h5 mb-0 font-display">{{ $election->title }}</h2>
          <span class="badge {{ $election->isOnline() ? 'bg-primary' : 'bg-secondary' }}">
            {{ ucfirst($election->type) }}
          </span>
          @if($election->isDraft())
            <span class="badge bg-secondary">Draft</span>
          @elseif($election->isOngoing())
            <span class="badge bg-success">Ongoing</span>
          @elseif($election->isClosed())
            <span class="badge bg-warning text-dark">Closed</span>
          @elseif($election->isFinalized())
            <span class="badge bg-info text-dark">Finalized</span>
          @endif
        </div>
        <p class="small text-muted-slate mb-0">
          Term: <strong>{{ $election->term }}</strong> | Voting Window: <strong>{{ $election->start_time->format('M j, Y g:i A') }}</strong> to <strong>{{ $election->end_time->format('M j, Y g:i A') }}</strong>
        </p>
      </div>

      <!-- Lifecycle Action Buttons -->
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="#positionsSection" class="btn btn-brass btn-sm">Candidates & Positions ↓</a>
        @if($election->isDraft())
          <a href="{{ route('admin.elections.edit', $election) }}" class="btn btn-outline-ink btn-sm">Edit Details</a>
          <form action="{{ route('admin.elections.start', $election) }}" method="POST" class="d-inline" onsubmit="return confirm('Start election and open voting?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success btn-sm">▶ Start Election</button>
          </form>
        @elseif($election->isOngoing())
          <form action="{{ route('admin.elections.close', $election) }}" method="POST" class="d-inline" onsubmit="return confirm('Close election voting?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-warning btn-sm">⏹ Close Election</button>
          </form>
        @elseif($election->isClosed())
          <form action="{{ route('admin.elections.finalize', $election) }}" method="POST" class="d-inline" onsubmit="return confirm('Finalize election results and generate Executive Committee? This cannot be undone.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-brass btn-sm">★ Finalize Results & Generate Committee</button>
          </form>
        @elseif($election->isFinalized())
          <a href="{{ route('elections.results.show', $election) }}" target="_blank" class="btn btn-outline-ink btn-sm">View Public Results ↗</a>
          @if($election->committee)
            <a href="{{ route('admin.committees.show', $election->committee) }}" class="btn btn-brass btn-sm">View Executive Committee</a>
          @endif
        @endif
      </div>
    </div>

    @if($election->description)
      <div class="small mt-3 pt-3 border-top text-muted-slate">
        {{ $election->description }}
      </div>
    @endif
  </div>

  <!-- Tie Resolution / Physical Winner Alert if Closed -->
  @if($election->isClosed() && $election->isOnline() && $resultsData && $resultsData['has_ties'])
    <div class="alert alert-warning mb-4">
      <h6 class="fw-bold mb-1">⚠️ Action Required: Tied Vote Count Detected</h6>
      <p class="small mb-0">One or more positions resulted in a tie for the top vote count. As an administrator, you must explicitly resolve each tie below before this election can be finalized.</p>
    </div>
  @endif

  @if($election->isClosed() && $election->isPhysical())
    <div class="alert alert-info mb-4">
      <h6 class="fw-bold mb-1">ℹ️ Physical Election Mode</h6>
      <p class="small mb-0">Individual votes are not recorded online for physical elections. Please select the designated winner for each position below before finalizing the election.</p>
    </div>
  @endif

  <!-- Positions & Candidate Management -->
  <div class="row g-4" id="positionsSection">
    <!-- Left Column: Positions & Candidates List -->
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="h5 mb-0 font-display">Positions &amp; Candidate Nomination</h3>
          <p class="small text-muted-slate mb-0">Every position must have at least one registered candidate before the election can start.</p>
        </div>
        <span class="badge bg-light text-dark border px-3 py-2 fs-6">
          {{ $election->positions->count() }} Positions | {{ $election->candidates->count() }} Candidates
        </span>
      </div>

      @if($election->positions->isEmpty())
        <div class="card-feature text-center py-5">
          <p class="small text-muted-slate mb-2">No positions added to this election yet.</p>
          <p class="small text-muted-slate mb-0">Use the "+ Add Position" form on the right to configure positions.</p>
        </div>
      @else
        @php
          $existingCandidateAlumniIds = $election->candidates->pluck('alumni_id')->all();
        @endphp
        @foreach($election->positions as $pos)
          @php
            $posResult = $resultsData ? collect($resultsData['results'])->firstWhere('position.id', $pos->id) : null;
          @endphp
          <div class="card-feature mb-4 border rounded p-4 bg-white shadow-sm" id="pos-card-{{ $pos->id }}">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
              <div class="d-flex align-items-center gap-2">
                <h4 class="h6 mb-0 font-display text-ink">{{ $pos->name }}</h4>
                <span class="badge bg-light text-dark border small">Order: {{ $pos->display_order }}</span>
                <span class="badge {{ $pos->candidates->isEmpty() ? 'bg-danger text-white' : 'bg-success text-white' }} small">
                  {{ $pos->candidates->count() }} {{ Str::plural('Candidate', $pos->candidates->count()) }}
                </span>
                @if($posResult && $posResult['is_tied'] && ! $posResult['is_resolved_tie'])
                  <span class="badge bg-warning text-dark small"><i class="bi bi-exclamation-triangle-fill"></i> TIED — Resolution Required</span>
                @elseif($pos->isTieResolved())
                  <span class="badge bg-info text-dark small">✓ Tie Resolved</span>
                @endif
              </div>
              @if($election->isDraft())
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-brass btn-sm py-1 px-3" onclick="toggleCandidateForm({{ $pos->id }}, event)" aria-expanded="false" aria-controls="addCandidateForm_{{ $pos->id }}">
                    + Add Candidate
                  </button>
                  <form action="{{ route('admin.elections.positions.destroy', [$election, $pos]) }}" method="POST" onsubmit="return confirm('Remove position {{ $pos->name }}?');" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2">Remove Position</button>
                  </form>
                </div>
              @endif
            </div>

            @if($posResult && $posResult['is_tied'] && ! $posResult['is_resolved_tie'])
              <div class="alert alert-warning py-2 px-3 small mb-3 border-warning">
                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Position is Tied:</strong>
                {{ $posResult['leaders']->count() }} candidates tied for highest vote count ({{ $posResult['max_votes'] }} {{ Str::plural('vote', $posResult['max_votes']) }}):
                <strong>{{ $posResult['leaders']->pluck('alumni.full_name')->join(', ') }}</strong>.
                <div class="text-muted-slate mt-1">
                  <em>No Winner badge is awarded while tied. An authorized election administrator must resolve this tie using the transparent resolution workflow below before finalization.</em>
                </div>
              </div>
            @elseif($pos->isTieResolved())
              <div class="alert alert-info py-2 px-3 small mb-3 border-info">
                <strong><i class="bi bi-check-circle-fill me-1"></i> Administrator-Resolved Tie:</strong>
                Method: <strong>{{ $pos->tie_resolution_method_label }}</strong>
                @if($pos->tie_resolution_reason)
                  &mdash; Reason: <em>"{{ $pos->tie_resolution_reason }}"</em>
                @endif
                <div class="text-secondary mt-1" style="font-size: 11px;">
                  Recorded by {{ $pos->tieResolvedBy?->name ?? 'Admin' }} on {{ $pos->tie_resolved_at?->format('M d, Y H:i') }}. Original vote counts remain intact.
                </div>
              </div>
            @endif

            <!-- Candidates inside this position -->
            @if($pos->candidates->isEmpty())
              <div class="p-3 mb-3 rounded bg-light border border-dashed text-center">
                <p class="small text-muted-slate mb-2">⚠️ No candidates registered for this position yet.</p>
                @if($election->isDraft())
                  <button type="button" class="btn btn-brass btn-sm" onclick="toggleCandidateForm({{ $pos->id }}, event)">
                    + Add Candidate to {{ $pos->name }}
                  </button>
                @endif
              </div>
            @else
              <div class="list-group list-group-flush mb-3">
                @foreach($pos->candidates as $cand)
                  <div class="list-group-item px-0 py-3 d-flex flex-wrap justify-content-between align-items-center border-bottom gap-3">
                    <div class="d-flex align-items-center gap-3">
                      @if($cand->photo_url)
                        <img src="{{ $cand->photo_url }}" alt="{{ $cand->alumni->full_name }}" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;">
                      @else
                        <div class="rounded-circle bg-brass d-flex align-items-center justify-content-center text-dark fw-bold fs-6" style="width: 48px; height: 48px;">
                          {{ substr($cand->alumni->full_name, 0, 1) }}
                        </div>
                      @endif
                      <div>
                        <div class="fw-semibold">
                          <a href="{{ route('admin.alumni.show', $cand->alumni) }}" class="text-decoration-none text-ink">
                            {{ $cand->alumni->full_name }}
                          </a>
                          @if($cand->is_winner)
                            @if($pos->isTieResolved())
                              <span class="badge bg-info text-dark ms-1">✓ Tie-Break Winner (Admin Resolved)</span>
                            @else
                              <span class="badge bg-success ms-1">Winner</span>
                            @endif
                          @endif
                        </div>
                        <div class="small text-muted-slate">
                          <strong>Student ID:</strong> {{ $cand->alumni->student_id }} | <strong>Batch:</strong> {{ $cand->alumni->batch?->batch_name ?? $cand->alumni->batch?->name ?? 'N/A' }}
                        </div>
                        @if($cand->statement)
                          <div class="small text-muted-slate mt-1 italic" style="max-width: 500px;">
                            "{{ Str::limit($cand->statement, 150) }}"
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                      <!-- Vote count for online elections if closed/finalized -->
                      @if($election->isOnline() && ! $election->isDraft())
                        <div class="text-end me-2">
                          <span class="badge bg-primary fs-6">{{ $cand->votes_count }}</span>
                          <div class="small text-muted-slate" style="font-size: 11px;">votes</div>
                        </div>
                      @endif

                      <!-- Manual Physical Winner Selection (if closed & physical) -->
                      @if($election->isClosed() && $election->isPhysical())
                        <form action="{{ route('admin.elections.select-physical-winner', $election) }}" method="POST">
                          @csrf
                          <input type="hidden" name="position_id" value="{{ $pos->id }}">
                          <input type="hidden" name="candidate_id" value="{{ $cand->id }}">
                          @if($cand->is_winner)
                            <span class="badge bg-success">Selected Winner</span>
                          @else
                            <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2">Set as Winner</button>
                          @endif
                        </form>
                      @endif

                      <!-- Tie Resolution (if closed & online & tied) -->
                      @if($election->isClosed() && $election->isOnline() && $resultsData && $resultsData['has_ties'])
                        @if($posResult && $posResult['is_tied'] && ! $posResult['is_resolved_tie'] && $posResult['leaders']->contains('id', $cand->id))
                          @can('manage', $election)
                            <button type="button" class="btn btn-warning btn-sm py-1 px-2"
                              onclick="openTieBreakModal({{ $pos->id }}, '{{ addslashes($pos->name) }}', {{ $cand->id }}, '{{ addslashes($cand->alumni->full_name) }}', {{ $cand->votes_count }})">
                              Select to Break Tie
                            </button>
                          @endcan
                        @endif
                      @endif

                      <!-- Candidate Edit & Delete (draft only) -->
                      @if($election->isDraft())
                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="toggleEditCandidateForm({{ $cand->id }}, event)">
                          Edit Candidate
                        </button>
                        <form action="{{ route('admin.elections.candidates.destroy', [$election, $cand]) }}" method="POST" onsubmit="return confirm('Remove candidate {{ $cand->alumni->full_name }}?');" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2">Remove</button>
                        </form>
                      @endif
                    </div>
                  </div>

                  <!-- Collapsible Edit Form for Candidate -->
                  @if($election->isDraft())
                    <div class="collapse mb-3 p-3 bg-light rounded border {{ old('candidate_id') == $cand->id ? 'show' : '' }}" id="editCandidateForm_{{ $cand->id }}" style="{{ old('candidate_id') == $cand->id ? 'display: block;' : '' }}">
                      <h5 class="h6 font-display mb-3">Edit Candidate: {{ $cand->alumni->full_name }}</h5>
                      <form action="{{ route('admin.elections.candidates.update', [$election, $cand]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="candidate_id" value="{{ $cand->id }}">
                        <div class="row g-2 mb-3">
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Running Position *</label>
                            <select name="election_position_id" class="form-select form-select-sm" required>
                              @foreach($election->positions as $p)
                                <option value="{{ $p->id }}" {{ (old('election_position_id', $cand->election_position_id) == $p->id) ? 'selected' : '' }}>
                                  {{ $p->name }}
                                </option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Candidate Photo</label>
                            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                          </div>
                          <div class="col-12">
                            <label class="form-label small fw-semibold">Candidate Statement / Manifesto</label>
                            <textarea name="statement" class="form-control form-control-sm" rows="3">{{ old('statement', $cand->statement) }}</textarea>
                          </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleEditCandidateForm({{ $cand->id }}, event)">Cancel</button>
                          <button type="submit" class="btn btn-brass btn-sm">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  @endif
                @endforeach
              </div>
              @if($election->isDraft())
                <div class="mt-2 mb-3">
                  <button type="button" class="btn btn-outline-brass btn-sm" onclick="toggleCandidateForm({{ $pos->id }}, event)">
                    + Add Candidate to {{ $pos->name }}
                  </button>
                </div>
              @endif
            @endif

            <!-- Collapsible Add Candidate Form inside this Position Card -->
            @if($election->isDraft())
              <div class="collapse mt-3 pt-3 border-top {{ old('election_position_id') == $pos->id ? 'show' : '' }}" id="addCandidateForm_{{ $pos->id }}" style="{{ old('election_position_id') == $pos->id ? 'display: block;' : '' }}">
                <div class="p-3 bg-light rounded border">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="h6 font-display mb-0">+ Add Candidate: {{ $pos->name }}</h5>
                    <button type="button" class="btn-close btn-sm" onclick="toggleCandidateForm({{ $pos->id }}, event)" aria-label="Close"></button>
                  </div>
                  <form action="{{ route('admin.elections.candidates.store', $election) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="election_position_id" value="{{ $pos->id }}">

                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Select Alumni *</label>
                      <select name="alumni_id" class="form-select form-select-sm" required>
                        <option value="">— Select Eligible Alumni —</option>
                        @foreach($alumniList as $al)
                          @php($isAlreadyNominated = in_array($al->id, $existingCandidateAlumniIds))
                          <option value="{{ $al->id }}" {{ $isAlreadyNominated ? 'disabled' : '' }}>
                            {{ $al->full_name }} — Student ID: {{ $al->student_id }} (Batch: {{ $al->batch?->batch_name ?? $al->batch?->name ?? 'N/A' }}){{ $isAlreadyNominated ? ' [Already Nominated]' : '' }}
                          </option>
                        @endforeach
                      </select>
                      <div class="form-text small text-muted-slate">Select a verified alumni record from the association database. Multiple candidates can run for {{ $pos->name }}.</div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Candidate Statement / Manifesto (Optional)</label>
                      <textarea name="statement" class="form-control form-control-sm" rows="3" placeholder="Brief campaign statement or manifesto for {{ $pos->name }}..."></textarea>
                    </div>

                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Candidate Photo (Optional)</label>
                      <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                      <div class="form-text small text-muted-slate">If omitted, the candidate's existing alumni profile photo will be used automatically.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCandidateForm({{ $pos->id }}, event)">Cancel</button>
                      <button type="submit" class="btn btn-brass btn-sm">+ Add Candidate to {{ $pos->name }}</button>
                    </div>
                  </form>
                </div>
              </div>
            @endif
          </div>
        @endforeach
      @endif
    </div>

    <!-- Right Column: Election Summary & Position Management -->
    <div class="col-lg-4">
      <!-- Election Summary Card -->
      <div class="card-feature mb-4">
        <h4 class="h6 mb-3 font-display">Election Overview</h4>
        <dl class="row small mb-0">
          <dt class="col-5 text-muted-slate">Status</dt>
          <dd class="col-7">
            <span class="badge {{ $election->isDraft() ? 'bg-secondary' : ($election->isOngoing() ? 'bg-success' : ($election->isClosed() ? 'bg-warning text-dark' : 'bg-info text-dark')) }}">
              {{ ucfirst($election->status) }}
            </span>
          </dd>
          <dt class="col-5 text-muted-slate">Type</dt>
          <dd class="col-7">{{ ucfirst($election->type) }}</dd>
          <dt class="col-5 text-muted-slate">Term</dt>
          <dd class="col-7">{{ $election->term }}</dd>
          <dt class="col-5 text-muted-slate">Positions</dt>
          <dd class="col-7"><strong>{{ $election->positions->count() }}</strong></dd>
          <dt class="col-5 text-muted-slate">Candidates</dt>
          <dd class="col-7"><strong>{{ $election->candidates->count() }}</strong></dd>
          <dt class="col-5 text-muted-slate">Created By</dt>
          <dd class="col-7">{{ $election->creator?->name ?? 'Admin' }}</dd>
        </dl>
      </div>

      @if($election->isDraft())
        <!-- Form: Add Position -->
        <div class="card-feature mb-4">
          <h4 class="h6 mb-3 font-display">+ Add Custom Position</h4>
          <p class="small text-muted-slate mb-3">Add any additional position to this election before starting.</p>
          <form action="{{ route('admin.elections.positions.store', $election) }}" method="POST">
            @csrf
            <div class="mb-2">
              <label class="form-label small fw-semibold">Position Name *</label>
              <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Assistant Organizing Secretary">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Display Order</label>
              <input type="number" name="display_order" class="form-control form-control-sm" value="{{ $election->positions->count() + 1 }}" min="1">
            </div>
            <button type="submit" class="btn btn-outline-ink btn-sm w-100">+ Add Position</button>
          </form>
        </div>

        <!-- Instructions Card -->
        <div class="card-feature">
          <h4 class="h6 mb-2 font-display">Candidate Rules</h4>
          <ul class="small text-muted-slate ps-3 mb-0">
            <li class="mb-1">Every position must have at least one registered candidate before the election can start.</li>
            <li class="mb-1">You can nominate multiple candidates for any position.</li>
            <li class="mb-1">Only verified alumni from the database can be nominated.</li>
            <li class="mb-1">Candidates can be edited or removed while the election is in draft.</li>
            <li>Once started, candidate positions and details are locked.</li>
          </ul>
        </div>
      @endif
    </div>
  </div>

  <script>
    function toggleCandidateForm(posId, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      var el = document.getElementById('addCandidateForm_' + posId);
      if (!el) return;
      var isVisible = el.classList.contains('show') || (el.style.display && el.style.display !== 'none');
      if (isVisible) {
        el.classList.remove('show');
        el.style.display = 'none';
      } else {
        el.classList.add('show');
        el.style.display = 'block';
        try { el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(e) {}
      }
    }

    function toggleEditCandidateForm(candId, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      var el = document.getElementById('editCandidateForm_' + candId);
      if (!el) return;
      var isVisible = el.classList.contains('show') || (el.style.display && el.style.display !== 'none');
      if (isVisible) {
        el.classList.remove('show');
        el.style.display = 'none';
      } else {
        el.classList.add('show');
        el.style.display = 'block';
        try { el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(e) {}
      }
    }

    function openTieBreakModal(posId, posName, candId, candName, votes) {
      document.getElementById('modalPositionId').value = posId;
      document.getElementById('modalCandidateId').value = candId;
      document.getElementById('modalPositionName').textContent = posName;
      document.getElementById('modalCandidateName').textContent = candName;
      document.getElementById('modalCandidateVotes').textContent = votes;
      document.getElementById('resolutionMethod').value = '';
      document.getElementById('resolutionReason').value = '';
      document.getElementById('confirmResolution').checked = false;

      var modalEl = document.getElementById('resolveTieModal');
      var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modal.show();
    }
  </script>

  <!-- Transparent Tie Resolution Modal -->
  <div class="modal fade" id="resolveTieModal" tabindex="-1" aria-labelledby="resolveTieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <form action="{{ route('admin.elections.resolve-tie', $election) }}" method="POST" id="tieBreakForm">
          @csrf
          <input type="hidden" name="position_id" id="modalPositionId">
          <input type="hidden" name="candidate_id" id="modalCandidateId">
          <div class="modal-header bg-warning-subtle border-bottom">
            <h5 class="modal-title font-display h6 mb-0 text-dark" id="resolveTieModalLabel">
              <i class="bi bi-award-fill me-1"></i> Transparent Tie-Breaking Resolution
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="alert alert-warning small mb-3">
              You are resolving a tie for position: <strong id="modalPositionName"></strong>.<br>
              Selected candidate: <strong id="modalCandidateName"></strong> (<span id="modalCandidateVotes"></span> votes).
            </div>

            <div class="mb-3">
              <label for="resolutionMethod" class="form-label small fw-semibold">Resolution Method *</label>
              <select name="resolution_method" id="resolutionMethod" class="form-select form-select-sm" required>
                <option value="">-- Choose Resolution Method --</option>
                <option value="committee_consensus">Executive Committee Consensus</option>
                <option value="lottery_draw">Official Lottery / Random Draw</option>
                <option value="academic_merit">Academic / Seniority Merit</option>
                <option value="runoff_outcome">Runoff Election Outcome</option>
                <option value="admin_discretion">Election Commission Determination</option>
              </select>
              <div class="form-text small">Select the governing procedure used to break this tie in accordance with alumni bylaws.</div>
            </div>

            <div class="mb-3">
              <label for="resolutionReason" class="form-label small fw-semibold">Resolution Details / Justification *</label>
              <textarea name="resolution_reason" id="resolutionReason" rows="3" class="form-control form-control-sm" required placeholder="Document the justification, meeting minutes reference, lottery draw witnesses, or committee agreement..."></textarea>
              <div class="form-text small">This reason will be logged in the permanent audit trail and displayed alongside certified results.</div>
            </div>

            <div class="form-check p-2 bg-light rounded border">
              <input class="form-check-input ms-0 me-2" type="checkbox" name="confirm_resolution" value="1" id="confirmResolution" required>
              <label class="form-check-label small fw-semibold text-ink" for="confirmResolution">
                I confirm this resolution is authentic, authorized by the Election Committee, and does not alter original voter counts.
              </label>
            </div>
          </div>
          <div class="modal-footer bg-light border-top">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning btn-sm fw-semibold">Declare Winner via Tie-Break</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
