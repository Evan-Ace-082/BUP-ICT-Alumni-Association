<p>BUP ICT Alumni Association</p>
<h2 style="margin-bottom: 4px;">{{ $event->title }}</h2>
<p style="color: #666; font-size: 13px; margin-top: 0;">
  {{ $event->event_type }} — {{ $event->event_date->format('F j, Y') }}
  @if ($event->venue) — {{ $event->venue }} @endif
</p>
@if ($event->batch)
  <p style="color: #666; font-size: 13px;">Targeted to: {{ $event->batch->batch_name }} ({{ $event->batch->batch_code }})</p>
@else
  <p style="color: #666; font-size: 13px;">For all alumni</p>
@endif
<div style="margin: 16px 0; white-space: pre-line;">{{ \Illuminate\Support\Str::limit($event->description, 300) }}</div>
<p><a href="{{ route('events.show', $event) }}">View this event on the platform</a></p>
<p style="color: #999; font-size: 12px;">You're receiving this because you're a registered alumnus of BUP ICT Alumni Association.</p>
