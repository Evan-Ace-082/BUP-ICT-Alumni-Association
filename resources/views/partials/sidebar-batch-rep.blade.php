<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Overview</a>
<a href="{{ route('dashboard.profile.edit') }}" class="{{ request()->routeIs('dashboard.profile.*') ? 'active' : '' }}">My Profile</a>
<a href="{{ route('batch-rep.alumni.index') }}" class="{{ request()->routeIs('batch-rep.alumni.*') ? 'active' : '' }}">My Batch Alumni</a>
<a href="{{ route('batch-rep.announcements.index') }}" class="{{ request()->routeIs('batch-rep.announcements.*') ? 'active' : '' }}">My Batch Announcements</a>
<a href="{{ route('batch-rep.events.index') }}" class="{{ request()->routeIs('batch-rep.events.*') ? 'active' : '' }}">My Batch Events</a>
<a href="{{ route('batch-rep.gallery.albums.index') }}" class="{{ request()->routeIs('batch-rep.gallery.*') ? 'active' : '' }}">My Batch Gallery</a>
