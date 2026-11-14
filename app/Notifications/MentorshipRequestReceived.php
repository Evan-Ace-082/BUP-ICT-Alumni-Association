<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipRequestReceived extends Notification
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

        $categoryName = $this->mentorshipRequest->category?->name ?? 'General Guidance';

        return (new MailMessage)
            ->subject('New Mentorship Request Received — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have received a new mentorship request from {$menteeName}.")
            ->line("Topic / Category: {$categoryName}")
            ->line("Specific question: \"{$this->mentorshipRequest->specific_topic}\"")
            ->action('Review Request', route('dashboard.mentorship.mentor'))
            ->line('Thank you for giving back to the BUP ICT community!');
    }

    public function toDatabase(object $notifiable): array
    {
        $menteeName = $this->mentorshipRequest->mentee->alumni?->full_name
            ?? $this->mentorshipRequest->mentee->student?->full_name
            ?? $this->mentorshipRequest->mentee->name;

        return [
            'type' => 'mentorship_request_received',
            'request_id' => $this->mentorshipRequest->id,
            'mentee_id' => $this->mentorshipRequest->mentee_id,
            'mentee_name' => $menteeName,
            'category' => $this->mentorshipRequest->category?->name,
            'topic' => $this->mentorshipRequest->specific_topic,
        ];
    }
}
