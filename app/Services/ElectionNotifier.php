<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Election;
use App\Models\User;

class ElectionNotifier
{
    public function __construct(
        protected AnnouncementNotifier $announcementNotifier
    ) {}

    public function notifyElectionOpened(Election $election, User $actor): void
    {
        try {
            $announcement = Announcement::create([
                'title' => "Online Election Now Open: {$election->title}",
                'body' => "Voting is officially open for {$election->title} (Term: {$election->term}). Eligible alumni are invited to visit their dashboard and cast their votes before {$election->end_time->format('M j, Y g:i A')}.",
                'target_type' => Announcement::TARGET_ALL,
                'batch_id' => null,
                'is_public' => true,
                'author_id' => $actor->id,
                'published_at' => now(),
            ]);

            $this->announcementNotifier->notify($announcement);
        } catch (\Throwable $e) {
            // Non-blocking notification dispatch
            report($e);
        }
    }
}
