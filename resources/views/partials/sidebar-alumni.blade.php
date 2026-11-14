<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Overview</a>
<a href="{{ route('dashboard.profile.edit') }}" class="{{ request()->routeIs('dashboard.profile.*') ? 'active' : '' }}">My Profile</a>
<a href="{{ route('dashboard.digital-id') }}" class="{{ request()->routeIs('dashboard.digital-id') ? 'active' : '' }}">My Digital ID</a>
<a href="{{ route('dashboard.batch') }}" class="{{ request()->routeIs('dashboard.batch') ? 'active' : '' }}">My Batch</a>
<a href="{{ route('dashboard.announcements.index') }}" class="{{ request()->routeIs('dashboard.announcements.*', 'dashboard.announcement.*') ? 'active' : '' }}">Announcements</a>
<a href="{{ route('dashboard.mentorship.index') }}" class="{{ request()->routeIs('dashboard.mentorship.index', 'dashboard.mentorship.show') ? 'active' : '' }}">Find a Mentor</a>
@if (auth()->user()?->mentorProfile)
  <a href="{{ route('dashboard.mentorship.mentor') }}" class="{{ request()->routeIs('dashboard.mentorship.mentor') ? 'active' : '' }}">Mentor Dashboard</a>
@elseif (auth()->user()?->alumni?->isVerified())
  <a href="{{ route('dashboard.mentorship.become-mentor') }}" class="{{ request()->routeIs('dashboard.mentorship.become-mentor') ? 'active' : '' }}">Become a Mentor</a>
@endif
<a href="{{ route('dashboard.mentorship.mentee') }}" class="{{ request()->routeIs('dashboard.mentorship.mentee') ? 'active' : '' }}">My Mentorships</a>
<a href="{{ route('dashboard.jobs.index') }}" class="{{ request()->routeIs('dashboard.jobs.*') ? 'active' : '' }}">Job Board</a>
<a href="{{ route('dashboard.achievements.index') }}" class="{{ request()->routeIs('dashboard.achievements.*') ? 'active' : '' }}">My Achievements</a>
<a href="{{ route('dashboard.elections.index') }}" class="{{ request()->routeIs('dashboard.elections.*') ? 'active' : '' }}">Elections &amp; Voting</a>
@if (auth()->user()?->hasRole('batch-representative'))
  <a href="{{ route('batch-rep.alumni.index') }}" class="{{ request()->routeIs('batch-rep.*') ? 'active' : '' }}">Batch Representative Panel</a>
@endif
@if (auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super-admin'))
  <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">Admin Panel</a>
@endif
