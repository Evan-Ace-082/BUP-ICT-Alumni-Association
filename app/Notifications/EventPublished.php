<?php

namespace App\Notifications;

use App\Mail\EventMail;
use App\Models\Event;
use Illuminate\Notifications\Notification;

/**
 * Sent only from the publish() actions (Admin/BatchRep EventController)
 * — never on create, edit, or cancel. Republishing an unpublished
 * event sends a fresh notification, matching the documented
 * AnnouncementPublished behavior: no "already notified" state is
 * tracked, so behavior is simple and predictable.
 */
class EventPublished extends Notification
{
    public function __construct(public Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): EventMail
    {
        return (new EventMail($this->event))->to($notifiable->email);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'event_type' => $this->event->event_type,
            'batch_id' => $this->event->batch_id,
        ];
    }
}
