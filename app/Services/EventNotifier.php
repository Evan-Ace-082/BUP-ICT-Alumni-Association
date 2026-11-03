<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventPublished;
use Illuminate\Support\Facades\Notification;

/**
 * Same recipient rule as AnnouncementNotifier: only users with a
 * claimed, approved/active alumni record are notified. An event with
 * no batch_id targets all alumni; an event with a batch_id targets
 * only that batch.
 */
class EventNotifier
{
    public function notify(Event $event): void
    {
        $recipients = $this->eligibleRecipients($event);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new EventPublished($event));
        }
    }

    protected function eligibleRecipients(Event $event)
    {
        $eligibleStatuses = [Alumni::STATUS_APPROVED, Alumni::STATUS_ACTIVE];

        return User::query()->whereHas('alumni', function ($q) use ($event, $eligibleStatuses) {
            $q->whereIn('status', $eligibleStatuses);

            if ($event->batch_id !== null) {
                $q->where('batch_id', $event->batch_id);
            }
        })->get();
    }
}
