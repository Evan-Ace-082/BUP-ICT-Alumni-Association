<?php

namespace App\Notifications;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use Illuminate\Notifications\Notification;

/**
 * Sent exactly once per publish() call (see
 * Admin/BatchRep AnnouncementController::publish()) — never on page
 * load, never on every edit. Republishing an unpublished announcement
 * sends a fresh notification; this project does not track "already
 * notified" state, so behavior is simple and predictable: every
 * transition into 'published' notifies the target audience again.
 *
 * Not marked ShouldQueue — no other job in this project currently
 * uses the queue, so keeping this synchronous avoids silently
 * requiring a queue worker to be running for something to work.
 * Add `implements ShouldQueue` here if that changes.
 */
class AnnouncementPublished extends Notification
{
    public function __construct(
        public Announcement $announcement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Laravel Notifications supports returning a full Mailable
     * instance directly from toMail(), which lets this stay
     * consistent with the existing AlumniOtpMail/Mailable convention
     * in this project instead of building a MailMessage inline.
     */
    public function toMail(object $notifiable): AnnouncementMail
    {
        return (new AnnouncementMail($this->announcement))->to($notifiable->email);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'target_type' => $this->announcement->target_type,
            'batch_id' => $this->announcement->batch_id,
            'excerpt' => str($this->announcement->body)->limit(140)->toString(),
        ];
    }
}
