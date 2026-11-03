@if ($errors->any())
  <div class="alert alert-danger small">
    @foreach ($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<div class="row g-3">
  <div class="col-12">
    <label class="form-label small" for="title">Title</label>
    <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $announcement->title) }}" required>
  </div>
  <div class="col-12">
    <label class="form-label small" for="body">Body</label>
    <textarea name="body" id="body" class="form-control" rows="5" required>{{ old('body', $announcement->body) }}</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="target_type">Target</label>
    <select name="target_type" id="target_type" class="form-select">
      <option value="all" @selected(old('target_type', $announcement->target_type) === 'all')>All Alumni</option>
      <option value="batch" @selected(old('target_type', $announcement->target_type) === 'batch')>Specific Batch</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label small" for="batch_id">Batch (only used when target is "Specific Batch")</label>
    <select name="batch_id" id="batch_id" class="form-select">
      <option value="">— Select a batch —</option>
      @foreach ($batches as $batch)
        <option value="{{ $batch->id }}" @selected(old('batch_id', $announcement->batch_id) == $batch->id)>
          {{ $batch->batch_name }} ({{ $batch->batch_code }})
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-12 form-check">
    <input type="hidden" name="is_public" value="0">
    <input type="checkbox" name="is_public" id="is_public" value="1" class="form-check-input"
           @checked(old('is_public', $announcement->is_public))>
    <label class="form-check-label small" for="is_public">
      Also show to guests (only relevant for a batch-specific announcement — "All Alumni" announcements are always public)
    </label>
  </div>
</div>
