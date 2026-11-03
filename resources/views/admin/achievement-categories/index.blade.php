@extends('layouts.dashboard')

@section('title', 'Achievement Categories & Sectors — BUP ICT Alumni Association')
@section('page_title', 'Achievement Categories & Sectors')

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

  <!-- Top Action Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex gap-2">
      <a href="{{ route('admin.achievements.index') }}" class="btn btn-sm btn-outline-secondary">
        &larr; Back to Achievement Submissions
      </a>
    </div>
    <a href="{{ route('admin.achievement-categories.create') }}" class="btn btn-brass">
      + Create New Category
    </a>
  </div>

  <!-- Status Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card-feature p-3 h-100 border text-center">
        <div class="small text-muted-slate text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Sectors</div>
        <div class="h4 mb-0 mt-1 fw-bold text-dark">{{ $counts['all'] }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature p-3 h-100 border text-center">
        <div class="small text-muted-slate text-uppercase fw-semibold" style="font-size: 0.75rem;">Active & Approved</div>
        <div class="h4 mb-0 mt-1 fw-bold text-success">{{ $counts['approved'] }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature p-3 h-100 border text-center">
        <div class="small text-muted-slate text-uppercase fw-semibold" style="font-size: 0.75rem;">Pending Suggestions</div>
        <div class="h4 mb-0 mt-1 fw-bold {{ $counts['pending'] > 0 ? 'text-warning' : 'text-dark' }}">
          {{ $counts['pending'] }}
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card-feature p-3 h-100 border text-center">
        <div class="small text-muted-slate text-uppercase fw-semibold" style="font-size: 0.75rem;">Deactivated</div>
        <div class="h4 mb-0 mt-1 fw-bold text-secondary">{{ $counts['inactive'] }}</div>
      </div>
    </div>
  </div>

  <!-- Main Management Card -->
  <div class="card-feature p-4">
    <!-- Filter Tabs & Search -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-3 border-bottom">
      <ul class="nav nav-pills gap-1">
        <li class="nav-item">
          <a class="nav-link py-1 px-3 small {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('admin.achievement-categories.index', ['tab' => 'all', 'search' => request('search')]) }}">
            All ({{ $counts['all'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link py-1 px-3 small {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('admin.achievement-categories.index', ['tab' => 'pending', 'search' => request('search')]) }}">
            Pending @if($counts['pending'] > 0)<span class="badge bg-warning text-dark ms-1">{{ $counts['pending'] }}</span>@endif
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link py-1 px-3 small {{ $tab === 'approved' ? 'active' : '' }}" href="{{ route('admin.achievement-categories.index', ['tab' => 'approved', 'search' => request('search')]) }}">
            Active ({{ $counts['approved'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link py-1 px-3 small {{ $tab === 'inactive' ? 'active' : '' }}" href="{{ route('admin.achievement-categories.index', ['tab' => 'inactive', 'search' => request('search')]) }}">
            Deactivated ({{ $counts['inactive'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link py-1 px-3 small {{ $tab === 'rejected' ? 'active' : '' }}" href="{{ route('admin.achievement-categories.index', ['tab' => 'rejected', 'search' => request('search')]) }}">
            Rejected ({{ $counts['rejected'] }})
          </a>
        </li>
      </ul>

      <form method="GET" action="{{ route('admin.achievement-categories.index') }}" class="d-flex gap-2" style="max-width: 300px;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search categories..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
      </form>
    </div>

    @if ($categories->isEmpty())
      <div class="placeholder-panel p-5 text-center">
        <h3 class="h6 mb-2">No categories found</h3>
        <p class="small text-muted mb-3">No categories match the selected filter or search keyword.</p>
        <a href="{{ route('admin.achievement-categories.index') }}" class="btn btn-sm btn-outline-primary">Reset Filters</a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover table-sm small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" style="min-width: 220px;">Category / Sector Name</th>
              <th scope="col">Status</th>
              <th scope="col">Active</th>
              <th scope="col" class="text-center">Achievements</th>
              <th scope="col">Suggested By</th>
              <th scope="col" class="text-end" style="min-width: 230px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($categories as $category)
              <tr>
                <td>
                  <div class="fw-semibold text-dark">{{ $category->name }}</div>
                  @if ($category->description)
                    <div class="text-muted" style="font-size: 0.8rem;">{{ \Illuminate\Support\Str::limit($category->description, 80) }}</div>
                  @endif
                  <div class="text-muted-slate font-monospace" style="font-size: 0.72rem;">slug: {{ $category->slug }}</div>
                </td>
                <td>
                  @if ($category->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                  @elseif ($category->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending Suggestion</span>
                  @else
                    <span class="badge bg-danger">Rejected</span>
                  @endif
                </td>
                <td>
                  @if ($category->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill bg-light text-dark border px-2 py-1">
                    {{ $category->achievements_count }}
                  </span>
                </td>
                <td>
                  @if ($category->suggester)
                    <div>{{ $category->suggester->name }}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ $category->suggester->email }}</div>
                  @else
                    <span class="text-muted">— System / Admin —</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1 align-items-center">
                    @if ($category->status === 'pending')
                      <!-- Approve Button -->
                      <form method="POST" action="{{ route('admin.achievement-categories.approve', $category) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success py-0 px-2" title="Approve category">Approve</button>
                      </form>
                      <!-- Reject Button -->
                      <form method="POST" action="{{ route('admin.achievement-categories.reject', $category) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Reject suggestion">Reject</button>
                      </form>
                    @endif

                    <!-- Toggle Active/Deactivate -->
                    <form method="POST" action="{{ route('admin.achievement-categories.toggle-active', $category) }}" class="d-inline">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="{{ $category->is_active ? 'Deactivate' : 'Activate' }}">
                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                      </button>
                    </form>

                    <!-- Edit -->
                    <a href="{{ route('admin.achievement-categories.edit', $category) }}" class="btn btn-sm btn-outline-ink py-0 px-2">Edit</a>

                    <!-- Delete Button / Modal Trigger -->
                    @if ($category->achievements_count > 0)
                      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $category->id }}">
                        Delete
                      </button>
                    @else
                      <form method="POST" action="{{ route('admin.achievement-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                      </form>
                    @endif
                  </div>

                  <!-- Safe Reassignment Deletion Modal if category has achievements -->
                  @if ($category->achievements_count > 0)
                    <div class="modal fade text-start" id="deleteModal{{ $category->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $category->id }}" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="POST" action="{{ route('admin.achievement-categories.destroy', $category) }}">
                            @csrf
                            @method('DELETE')
                            <div class="modal-header">
                              <h5 class="modal-title h6" id="deleteModalLabel{{ $category->id }}">Delete Category: {{ $category->name }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <div class="alert alert-warning small">
                                <strong>Notice:</strong> This category is currently associated with <strong>{{ $category->achievements_count }}</strong> achievement(s).
                                To safely delete this category without data loss, you must select another active category to reassign those achievements to, or cancel and deactivate this category instead.
                              </div>

                              <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reassign_{{ $category->id }}">Reassign Achievements To <span class="text-danger">*</span></label>
                                <select name="reassign_to_id" id="reassign_{{ $category->id }}" class="form-select form-select-sm" required>
                                  <option value="">-- Choose Target Category --</option>
                                  @foreach ($allCategories as $target)
                                    @if ($target->id !== $category->id)
                                      <option value="{{ $target->id }}">{{ $target->name }}</option>
                                    @endif
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-sm btn-danger">Reassign & Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-4">{{ $categories->links() }}</div>
    @endif
  </div>
@endsection
