<p>BUP ICT Alumni Association</p>

<h2 style="margin-bottom: 4px;">{{ $announcement->title }}</h2>

@if ($announcement->target_type === \App\Models\Announcement::TARGET_BATCH && $announcement->batch)
  <p style="color: #666; font-size: 13px; margin-top: 0;">
    Targeted to: {{ $announcement->batch->batch_name }} ({{ $announcement->batch->batch_code }})
  </p>
@else
  <p style="color: #666; font-size: 13px; margin-top: 0;">For all alumni</p>
@endif

<div style="margin: 16px 0; white-space: pre-line;">{{ $announcement->body }}</div>

@if ($announcement->published_at)
  <p style="color: #666; font-size: 12px;">
    Published {{ $announcement->published_at->format('F j, Y g:i A') }}
  </p>
@endif

<p>
  <a href="{{ route('announcements.show', $announcement) }}">View this announcement on the platform</a>
</p>

<p style="color: #999; font-size: 12px;">
  You're receiving this because you're a registered alumnus of BUP ICT Alumni Association.
</p>
