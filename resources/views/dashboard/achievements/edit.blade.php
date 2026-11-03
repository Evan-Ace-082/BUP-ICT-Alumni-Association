@extends('layouts.dashboard')

@section('title', 'Edit Achievement — BUP ICT Alumni Association')
@section('page_title', 'Edit Achievement')

@section('content')
  <a href="{{ route('dashboard.achievements.index') }}" class="small d-inline-block mb-3 text-decoration-none text-muted-slate">&larr; Back to My Achievements</a>

  <div class="card-feature p-4">
    <div class="mb-4">
      <h2 class="h5 mb-1">Edit Pending Achievement</h2>
      <p class="small text-muted mb-0">Update details for your pending submission before administrator review.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger small mb-4">
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('dashboard.achievements.update', $achievement) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="title">Achievement Title <span class="text-danger">*</span></label>
        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $achievement->title) }}" required>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="achievement_category_id">Category / Sector <span class="text-danger">*</span></label>
        <select name="achievement_category_id" id="achievement_category_id" class="form-select" required>
          <option value="">-- Select a Category / Sector --</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}" data-name="{{ $cat->name }}"
              @selected(old('achievement_category_id', $achievement->achievement_category_id) == $cat->id)>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Custom Sector Suggestion Field (shows when "Others" is selected) -->
      <div class="mb-3 p-3 bg-light rounded border" id="customSectorGroup" style="display: none;">
        <label class="form-label small fw-semibold text-primary" for="custom_category">
          Suggest a New Category / Sector Name
        </label>
        <input type="text" name="custom_category" id="custom_category" class="form-control" value="{{ old('custom_category') }}" placeholder="e.g. Artificial Intelligence, Healthcare, Cybersecurity">
        <div class="form-text small text-muted">
          If you want to suggest a new sector, enter it here.
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold" for="organization">Organization / Institution</label>
          <input type="text" name="organization" id="organization" class="form-control" value="{{ old('organization', $achievement->organization) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold" for="achievement_date">Achievement Date</label>
          <input type="date" name="achievement_date" id="achievement_date" class="form-control" value="{{ old('achievement_date', optional($achievement->achievement_date)->format('Y-m-d')) }}">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="description">Detailed Description <span class="text-danger">*</span></label>
        <textarea name="description" id="description" class="form-control" rows="5" required>{{ old('description', $achievement->description) }}</textarea>
      </div>

      <div class="mb-4">
        <label class="form-label small fw-semibold" for="image">Optional Certificate / Achievement Image</label>
        @if ($achievement->image)
          <div class="mb-2">
            <img src="{{ asset('storage/' . $achievement->image) }}" alt="Current Image" class="rounded border" style="max-height: 120px;">
            <div class="small text-muted mt-1">Current file uploaded. Upload a new file below to replace it.</div>
          </div>
        @endif
        <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-brass">Save Changes</button>
        <a href="{{ route('dashboard.achievements.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const categorySelect = document.getElementById('achievement_category_id');
      const customSectorGroup = document.getElementById('customSectorGroup');
      const customInput = document.getElementById('custom_category');

      function toggleCustomSector() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        if (!selectedOption) return;

        const isOthers = (selectedOption.dataset.slug === 'others' ||
                          selectedOption.dataset.name === 'Others' ||
                          selectedOption.text.trim().toLowerCase() === 'others');

        if (isOthers) {
          customSectorGroup.style.display = 'block';
        } else {
          customSectorGroup.style.display = 'none';
        }
      }

      categorySelect.addEventListener('change', toggleCustomSector);
      toggleCustomSector();
    });
  </script>
@endsection
