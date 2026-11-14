<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipCompleted extends Notification
{
    use Queueable;

    public function __construct(public MentorshipRequest $mentorshipRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mentorship Marked as Completed — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your mentorship engagement has been marked as completed.')
            ->line('We hope this mentorship experience contributed to your growth and knowledge sharing.')
            ->action('View History', route('dashboard.mentorship.index'))
            ->line('Thank you for being an active part of the BUP ICT Alumni community!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mentorship_completed',
            'request_id' => $this->mentorshipRequest->id,
            'completed_at' => $this->mentorshipRequest->completed_at,
        ];
    }
}
