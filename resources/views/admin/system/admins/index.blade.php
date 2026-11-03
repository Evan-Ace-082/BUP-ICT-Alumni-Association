@extends('layouts.dashboard')
@section('title', 'Admin Management — BUP ICT Alumni Association')
@section('page_title', 'Admin Management')
@section('content')
  @if (session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
  @if ($errors->any())<div class="alert alert-danger small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

  <div class="card-feature">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0">Admin Accounts</h2>
      <a href="{{ route('admin.system.admins.create') }}" class="btn btn-brass">New Admin</a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm small align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
          @foreach ($admins as $admin)
            <tr>
              <td>{{ $admin->name }}</td>
              <td>{{ $admin->email }}</td>
              <td>{{ $admin->hasRole('super-admin') ? 'Super Admin' : 'Admin' }}</td>
              <td><span class="badge {{ $admin->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($admin->status) }}</span></td>
              <td>{{ $admin->created_at->format('M j, Y') }}</td>
              <td class="d-flex gap-2">
                @if ($admin->hasRole('super-admin'))
                  <span class="small text-muted-slate">Super Admin — no self-management controls</span>
                @elseif ($admin->id === auth()->id())
                  <span class="small text-muted-slate">This is you</span>
                @else
                  @if ($admin->status === 'active')
                    <form method="POST" action="{{ route('admin.system.admins.suspend', $admin) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-ink">Suspend</button></form>
                  @else
                    <form method="POST" action="{{ route('admin.system.admins.restore', $admin) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-brass">Restore</button></form>
                  @endif
                  <form method="POST" action="{{ route('admin.system.admins.remove-role', $admin) }}" onsubmit="return confirm('Remove this user\'s admin role?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-ink">Remove Admin</button></form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
