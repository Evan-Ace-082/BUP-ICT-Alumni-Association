@if (request()->routeIs('admin.*'))
  @include('partials.sidebar-admin')
@elseif (request()->routeIs('batch-rep.*'))
  @include('partials.sidebar-batch-rep')
@else
  @include('partials.sidebar-alumni')
@endif
