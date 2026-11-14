@extends('layouts.dashboard')
@section('title', 'Mentorship Categories — Admin Panel')
@section('page_title', 'Mentorship Categories')

@section('content')
  @if (session('status'))
    <div class="alert alert-success small mb-4">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger small mb-4">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h1 class="h5 mb-0">Mentorship Categories &amp; Fields</h1>
      <p class="small text-muted-slate mb-0">Manage the 15+ mentorship domains available for senior alumni mentors and mentees.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-brass btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        + Add New Category
      </button>
      <a href="{{ route('admin.mentorship.index') }}" class="btn btn-outline-ink btn-sm">&larr; Back to Overview</a>
    </div>
  </div>

  <div class="card-feature p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-muted-slate">
            <th style="width: 50px;">Order</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Mentors</th>
            <th>Requests</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="small">
          @foreach ($categories as $cat)
            <tr>
              <td class="fw-semibold text-muted-slate">{{ $cat->sort_order }}</td>
              <td>
                <div class="fw-semibold text-ink-900">{{ $cat->name }}</div>
                <div class="text-muted-slate" style="font-size: 11px;">Slug: {{ $cat->slug }}</div>
              </td>
              <td>
                <div class="text-muted-slate" style="max-width: 320px;">{{ $cat->description ?: '—' }}</div>
              </td>
              <td>
                <span class="badge bg-brass-subtle text-brass">{{ $cat->mentors_count }} mentors</span>
              </td>
              <td>
                <span class="badge bg-light border text-muted-slate">{{ $cat->requests_count }} requests</span>
              </td>
              <td>
                @if ($cat->is_active)
                  <span class="badge bg-success-subtle text-success">Active</span>
                @else
                  <span class="badge bg-secondary">Inactive</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-flex justify-content-end gap-1">
                  <button type="button" class="btn btn-outline-ink btn-sm py-1 px-2" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#editCatModal{{ $cat->id }}">
                    Edit
                  </button>
                  <form method="POST" action="{{ route('admin.mentorship.categories.toggle', $cat) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $cat->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} btn-sm py-1 px-2" style="font-size: 11px;">
                      {{ $cat->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </form>
                </div>
              </td>
            </tr>

            {{-- Edit Category Modal --}}
            <div class="modal fade" id="editCatModal{{ $cat->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" action="{{ route('admin.mentorship.categories.update', $cat) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                      <h5 class="modal-title h6">Edit Category: {{ $cat->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $cat->name }}" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" rows="2" class="form-control form-control-sm">{{ $cat->description }}</textarea>
                      </div>
                      <div class="row g-2 mb-3">
                        <div class="col-6">
                          <label class="form-label small fw-semibold">Sort Order</label>
                          <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $cat->sort_order }}">
                        </div>
                        <div class="col-6">
                          <label class="form-label small fw-semibold">Icon Identifier</label>
                          <input type="text" name="icon" class="form-control form-control-sm" value="{{ $cat->icon }}">
                        </div>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_active_{{ $cat->id }}" value="1" {{ $cat->is_active ? 'checked' : '' }}>
                        <label class="form-check-label small" for="edit_active_{{ $cat->id }}">Category is active and selectable</label>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-brass btn-sm">Update Category</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Create Category Modal --}}
  <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.mentorship.categories.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title h6">Add New Mentorship Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Cloud Architecture &amp; DevOps" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Description</label>
              <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Brief guidance summary..."></textarea>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold">Sort Order</label>
                <input type="number" name="sort_order" class="form-control form-control-sm" placeholder="e.g. 16">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Icon Name (Optional)</label>
                <input type="text" name="icon" class="form-control form-control-sm" placeholder="e.g. cloud">
              </div>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" id="create_active" value="1" checked>
              <label class="form-check-label small" for="create_active">Active immediately</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-ink btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-brass btn-sm">Create Category</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
