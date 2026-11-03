<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Support\Facades\Notification;

/**
 * Notification targeting is intentionally separate from announcement
 * VIEWING visibility (Announcement::scopeVisibleToUser) — only
 * recipients with a claimed, currently-loggable-in account are
 * notified. Someone can still be able to VIEW a general announcement
 * without ever having registered; they just won't get a notification
 * for it, since there's no account to notify.
 */
class AnnouncementNotifier
{
    public function notify(Announcement $announcement): void
    {
        $recipients = $this->eligibleRecipients($announcement);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AnnouncementPublished($announcement));
        }
    }

    protected function eligibleRecipients(Announcement $announcement)
    {
        $eligibleStatuses = [Alumni::STATUS_APPROVED, Alumni::STATUS_ACTIVE];

        $query = User::query()->whereHas('alumni', function ($q) use ($announcement, $eligibleStatuses) {
            $q->whereIn('status', $eligibleStatuses);

            if ($announcement->target_type === Announcement::TARGET_BATCH) {
                $q->where('batch_id', $announcement->batch_id);
            }
        });

        return $query->get();
    }
}
