<a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}">Overview</a>
<a href="{{ route('admin.batches.index') }}" class="{{ request()->routeIs('admin.batches.*') ? 'active' : '' }}">Batches</a>
<a href="{{ route('admin.alumni.index') }}" class="{{ request()->routeIs('admin.alumni.index', 'admin.alumni.show') ? 'active' : '' }}">Alumni Records</a>
<a href="{{ route('admin.alumni-network.index') }}" class="{{ request()->routeIs('admin.alumni-network.*') ? 'active' : '' }}">Alumni Network</a>
<a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements.*', 'admin.announcement.*') ? 'active' : '' }}">Announcements</a>
<a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}">Events</a>
<a href="{{ route('admin.gallery.albums.index') }}" class="{{ request()->routeIs('admin.gallery.*') ? 'active' : '' }}">Gallery</a>
<a href="{{ route('admin.achievements.index') }}" class="{{ request()->routeIs('admin.achievements.*') ? 'active' : '' }}">Achievements</a>
<a href="{{ route('admin.achievement-categories.index') }}" class="{{ request()->routeIs('admin.achievement-categories.*') ? 'active' : '' }}">Achievement Sectors</a>
<a href="{{ route('admin.jobs.index') }}" class="{{ request()->routeIs('admin.jobs.*') ? 'active' : '' }}">Jobs</a>
<a href="{{ route('admin.elections.index') }}" class="{{ request()->routeIs('admin.elections.*') ? 'active' : '' }}">Elections</a>
<a href="{{ route('admin.committees.index') }}" class="{{ request()->routeIs('admin.committees.*') ? 'active' : '' }}">Executive Committees</a>
<a href="{{ route('admin.alumni-import.index') }}" class="{{ request()->routeIs('admin.alumni-import.*') ? 'active' : '' }}">Alumni Import</a>
<a href="{{ route('admin.verifications.index') }}" class="{{ request()->routeIs('admin.verifications.*') ? 'active' : '' }}">Verification Requests</a>
<a href="{{ route('admin.alumni-verifications.index') }}" class="{{ request()->routeIs('admin.alumni-verifications.*') ? 'active' : '' }}">Alumni Verifications</a>
@if (auth()->user()?->hasRole('super-admin'))
  <div class="small text-muted-slate mt-3 mb-1" style="padding: 0 1.25rem;">System Administration</div>
  <a href="{{ route('admin.system.admins.index') }}" class="{{ request()->routeIs('admin.system.admins.*') ? 'active' : '' }}">Admin Management</a>
  <a href="{{ route('admin.system.audit-log.index') }}" class="{{ request()->routeIs('admin.system.audit-log.*') ? 'active' : '' }}">Audit Log</a>
@endif
