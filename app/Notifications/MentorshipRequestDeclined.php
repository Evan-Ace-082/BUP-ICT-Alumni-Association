<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipRequestDeclined extends Notification
{
    use Queueable;

    public function __construct(public MentorshipRequest $mentorshipRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mentorName = $this->mentorshipRequest->mentor->alumni?->full_name ?? $this->mentorshipRequest->mentor->name;

        $mail = (new MailMessage)
            ->subject('Update on Your Mentorship Request — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$mentorName} is unable to accept your mentorship request at this time.");

        if ($this->mentorshipRequest->decline_reason) {
            $mail->line("Note from mentor: \"{$this->mentorshipRequest->decline_reason}\"");
        }

        return $mail
            ->line('Do not be discouraged! You can browse other mentors in the directory.')
            ->action('Find Another Mentor', route('dashboard.mentorship.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $mentorName = $this->mentorshipRequest->mentor->alumni?->full_name ?? $this->mentorshipRequest->mentor->name;

        return [
            'type' => 'mentorship_request_declined',
            'request_id' => $this->mentorshipRequest->id,
            'mentor_id' => $this->mentorshipRequest->mentor_id,
            'mentor_name' => $mentorName,
            'decline_reason' => $this->mentorshipRequest->decline_reason,
        ];
    }
}
