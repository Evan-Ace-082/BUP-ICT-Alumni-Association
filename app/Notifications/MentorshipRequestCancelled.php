<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipRequestCancelled extends Notification
{
    use Queueable;

    public function __construct(public MentorshipRequest $mentorshipRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $menteeName = $this->mentorshipRequest->mentee->alumni?->full_name
            ?? $this->mentorshipRequest->mentee->student?->full_name
            ?? $this->mentorshipRequest->mentee->name;

        return (new MailMessage)
            ->subject('Mentorship Request Cancelled — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},")
            ->line("The mentorship request from {$menteeName} was cancelled by the mentee.")
            ->action('View Mentor Dashboard', route('dashboard.mentorship.mentor'));
    }

    public function toDatabase(object $notifiable): array
    {
        $menteeName = $this->mentorshipRequest->mentee->alumni?->full_name
            ?? $this->mentorshipRequest->mentee->student?->full_name
            ?? $this->mentorshipRequest->mentee->name;

        return [
            'type' => 'mentorship_request_cancelled',
            'request_id' => $this->mentorshipRequest->id,
            'mentee_id' => $this->mentorshipRequest->mentee_id,
            'mentee_name' => $menteeName,
        ];
    }
}
