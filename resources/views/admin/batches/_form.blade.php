@if ($errors->any())
  <div class="alert alert-danger small">
    @foreach ($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label small" for="batch_name">Batch Name</label>
    <input
      type="text"
      name="batch_name"
      id="batch_name"
      class="form-control"
      value="{{ old('batch_name', $batch->batch_name) }}"
      placeholder="e.g. Batch 09"
      required
    >
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="batch_code">Batch Code</label>
    <input
      type="text"
      name="batch_code"
      id="batch_code"
      class="form-control"
      value="{{ old('batch_code', $batch->batch_code) }}"
      placeholder="e.g. BICE-2020"
      required
    >
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="session">Session</label>
    <input
      type="text"
      name="session"
      id="session"
      class="form-control"
      value="{{ old('session', $batch->session) }}"
      placeholder="e.g. 2015-2016"
    >
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="graduation_year">Graduation Year</label>
    <input
      type="number"
      name="graduation_year"
      id="graduation_year"
      class="form-control"
      value="{{ old('graduation_year', $batch->graduation_year) }}"
      placeholder="e.g. 2020"
    >
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="status">Status</label>
    <select name="status" id="status" class="form-select">
      <option value="active" @selected(old('status', $batch->status) === 'active')>Active</option>
      <option value="inactive" @selected(old('status', $batch->status) === 'inactive')>Inactive</option>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label small" for="description">Description</label>
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $batch->description) }}</textarea>
  </div>
</div>
