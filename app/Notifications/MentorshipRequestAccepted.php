<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipRequestAccepted extends Notification
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
        $mentorEmail = $this->mentorshipRequest->mentor->email;

        return (new MailMessage)
            ->subject('Your Mentorship Request Has Been Accepted! — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! {$mentorName} has accepted your mentorship request.")
            ->line("You can now connect with your mentor to schedule your sessions.")
            ->line("Mentor Contact Email: {$mentorEmail}")
            ->action('View Mentorship Details', route('dashboard.mentorship.mentee'))
            ->line('We wish you a fruitful and rewarding mentorship experience.');
    }

    public function toDatabase(object $notifiable): array
    {
        $mentorName = $this->mentorshipRequest->mentor->alumni?->full_name ?? $this->mentorshipRequest->mentor->name;

        return [
            'type' => 'mentorship_request_accepted',
            'request_id' => $this->mentorshipRequest->id,
            'mentor_id' => $this->mentorshipRequest->mentor_id,
            'mentor_name' => $mentorName,
        ];
    }
}
