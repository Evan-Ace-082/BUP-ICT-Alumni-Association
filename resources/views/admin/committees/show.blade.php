@extends('layouts.dashboard')

@section('title', 'Executive Committee (' . $committee->term . ') — Management')
@section('page_title', 'Executive Committee Management')


@section('content')
  @if(session('success'))
    <div class="alert alert-success small mb-4">{{ session('success') }}</div>
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

  <div class="card-feature mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h2 class="h5 mb-0 font-display">Executive Committee — Term {{ $committee->term }}</h2>
          @if($committee->is_current)
            <span class="badge bg-success">Current Active Committee</span>
          @else
            <span class="badge bg-secondary">Historical</span>
          @endif
        </div>
        <p class="small text-muted-slate mb-0">
          Origin: <strong>{{ $committee->election ? "Elected via '{$committee->election->title}'" : 'Manual Administrative Setup' }}</strong>
          | Total Members: <strong>{{ $committee->members->count() }}</strong>
        </p>
      </div>

      <div class="d-flex gap-2 align-items-center">
        @if(! $committee->is_current)
          <form action="{{ route('admin.committees.activate', $committee) }}" method="POST" onsubmit="return confirm('Activate this committee as the current Executive Committee?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success btn-sm">Set as Current Committee</button>
          </form>
        @endif
        <a href="#addMemberCard" class="btn btn-brass btn-sm" onclick="var el = document.getElementById('addMemberCard'); if(el) el.scrollIntoView({ behavior: 'smooth' }); var sel = document.getElementById('alumniSelect'); if(sel) sel.focus();">+ Add Member</a>
        <a href="{{ route('admin.committees.edit', $committee) }}" class="btn btn-outline-ink btn-sm">Edit Term</a>
        <a href="{{ route('committees.show', $committee) }}" target="_blank" class="btn btn-outline-ink btn-sm">Public View ↗</a>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Member List -->
    <div class="col-lg-8">
      <div class="card-feature">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h6 mb-0 font-display">Committee Members</h3>
          <a href="#addMemberCard" class="btn btn-brass btn-sm">+ Add Member</a>
        </div>

        @if($committee->members->isEmpty())
          <div class="placeholder-panel text-center py-4">
            <p class="small text-muted-slate mb-2">No members have been assigned to this committee yet.</p>
            <a href="#addMemberCard" class="btn btn-brass btn-sm">+ Add Member</a>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Member Name</th>
                  <th>Designation</th>
                  <th>Batch / Student ID</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($committee->members as $member)
                  <tr>
                    <td class="text-muted-slate">{{ $member->display_order }}</td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        @if($member->photo_url)
                          <img src="{{ $member->photo_url }}" alt="{{ $member->alumni->full_name }}" class="rounded shadow-sm" style="width: 34px; height: 34px; object-fit: cover;">
                        @else
                          <div class="rounded bg-parchment d-flex align-items-center justify-content-center text-muted-slate fw-bold" style="width: 34px; height: 34px; font-size: 11px;">
                            {{ substr($member->alumni->full_name, 0, 1) }}
                          </div>
                        @endif
                        <div>
                          <a href="{{ route('admin.alumni.show', $member->alumni) }}" class="fw-semibold text-decoration-none">
                            {{ $member->alumni->full_name }}
                          </a>
                          @if($member->photo)
                            <span class="badge bg-light text-muted-slate border ms-1" style="font-size: 10px;">Custom Photo</span>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border">{{ $member->position_name }}</span>
                    </td>
                    <td class="text-muted-slate">
                      {{ $member->alumni->batch?->batch_name ?? $member->alumni->batch?->name ?? '—' }} ({{ $member->alumni->student_id }})
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleEditMemberRow({{ $member->id }}, event)">Edit</button>
                      <form action="{{ route('admin.committees.members.destroy', [$committee, $member]) }}" method="POST" onsubmit="return confirm('Remove {{ $member->alumni->full_name }} from this committee?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">Remove</button>
                      </form>
                    </td>
                  </tr>

                  <!-- Collapsible Edit Row -->
                  <tr id="editMemberRow_{{ $member->id }}" style="display: none;" class="bg-light">
                    <td colspan="5" class="p-3">
                      <form action="{{ route('admin.committees.members.update', [$committee, $member]) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                          <label class="form-label small fw-semibold">Designation for {{ $member->alumni->full_name }} *</label>
                          <select name="position_name" class="form-select form-select-sm" required>
                            @foreach(['President', 'Vice President', 'General Secretary', 'Joint Secretary', 'Treasurer', 'Organizing Secretary', 'Executive Member', 'Logistics', 'Assistant General Secretary', 'Assistant Joint Secretary'] as $desig)
                              <option value="{{ $desig }}" {{ $member->position_name === $desig ? 'selected' : '' }}>{{ $desig }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-2">
                          <label class="form-label small fw-semibold">Display Order</label>
                          <input type="number" name="display_order" class="form-control form-control-sm" value="{{ $member->display_order }}" min="0">
                        </div>
                        <div class="col-md-3">
                          <label class="form-label small fw-semibold">Photo (Optional override)</label>
                          <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                          @if($member->photo)
                            <div class="form-check mt-1">
                              <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="rmPhoto_{{ $member->id }}">
                              <label class="form-check-label text-muted-slate" style="font-size:11px;" for="rmPhoto_{{ $member->id }}">Remove photo override</label>
                            </div>
                          @endif
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                          <button type="submit" class="btn btn-brass btn-sm">Save Changes</button>
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleEditMemberRow({{ $member->id }}, event)">Cancel</button>
                        </div>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <!-- Right Column: Add Member Form -->
    <div class="col-lg-4">
      <div class="card-feature" id="addMemberCard">
        <h4 class="h6 mb-3 font-display">+ Add Committee Member</h4>
        <form action="{{ route('admin.committees.members.store', $committee) }}" method="POST" enctype="multipart/form-data">
          @csrf

          @php
            $existingMemberAlumniIds = $committee->members->pluck('alumni_id')->all();
          @endphp
          <div class="mb-2">
            <label class="form-label small fw-semibold">Alumni *</label>
            <select name="alumni_id" id="alumniSelect" class="form-select form-select-sm" required>
              <option value="">— Select Alumni —</option>
              @foreach($alumniList as $al)
                @php($isAlreadyMember = in_array($al->id, $existingMemberAlumniIds))
                <option value="{{ $al->id }}" {{ $isAlreadyMember ? 'disabled' : '' }}>
                  {{ $al->full_name }} — Student ID: {{ $al->student_id }} (Batch: {{ $al->batch?->batch_name ?? $al->batch?->name ?? 'N/A' }}){{ $isAlreadyMember ? ' [Already in Committee]' : '' }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Designation / Position *</label>
            <select name="position_name" id="designationSelect" class="form-select form-select-sm" required onchange="handleDesignationChange(this)">
              <option value="">— Select Designation —</option>
              <option value="President">President</option>
              <option value="Vice President">Vice President</option>
              <option value="General Secretary">General Secretary</option>
              <option value="Joint Secretary">Joint Secretary</option>
              <option value="Treasurer">Treasurer</option>
              <option value="Organizing Secretary">Organizing Secretary</option>
              <option value="Executive Member">Executive Member</option>
              <option value="Logistics">Logistics</option>
              <option value="Assistant General Secretary">Assistant General Secretary</option>
              <option value="Assistant Joint Secretary">Assistant Joint Secretary</option>
              <option value="__custom__">Custom Designation...</option>
            </select>
            <input type="text" name="custom_position_name" id="customDesignationInput" class="form-control form-control-sm mt-1" placeholder="Enter custom designation" style="display: none;">
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Photo (Optional override)</label>
            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
            <div class="form-text small text-muted-slate" style="font-size: 11px;">If left blank, the alumni's profile photo is used automatically.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Display Order</label>
            <input type="number" name="display_order" class="form-control form-control-sm" value="{{ $committee->members->count() + 1 }}" min="0">
          </div>

          <button type="submit" class="btn btn-brass btn-sm w-100">+ Add Member</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function handleDesignationChange(selectEl) {
      var customInput = document.getElementById('customDesignationInput');
      if (!customInput) return;
      if (selectEl.value === '__custom__') {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
      } else {
        customInput.style.display = 'none';
        customInput.required = false;
      }
    }

    function toggleEditMemberRow(memberId, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      var row = document.getElementById('editMemberRow_' + memberId);
      if (!row) return;
      if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
      } else {
        row.style.display = 'none';
      }
    }
  </script>
@endsection
