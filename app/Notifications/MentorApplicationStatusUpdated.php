<?php

namespace App\Notifications;

use App\Models\MentorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorApplicationStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public MentorProfile $mentorProfile) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->mentorProfile->status;

        $mail = (new MailMessage)
            ->subject('Mentor Application Update — BUP ICT Alumni')
            ->greeting("Hello {$notifiable->name},");

        if ($status === MentorProfile::STATUS_APPROVED) {
            $mail->line('Congratulations! Your application to become an official BUP ICT Alumni Mentor has been approved by the department administrators.')
                ->line('Your profile is now live in the Find a Mentor directory. You can manage your availability, schedule, and incoming requests from your Mentor Dashboard.')
                ->action('Go to Mentor Dashboard', route('dashboard.mentorship.mentor'));
        } elseif ($status === MentorProfile::STATUS_REJECTED) {
            $mail->line('Thank you for your interest in the BUP ICT Mentorship program. At this time, your mentor application could not be approved.');
            if ($this->mentorProfile->rejection_reason) {
                $mail->line("Administrative feedback: \"{$this->mentorProfile->rejection_reason}\"");
            }
        } elseif ($status === MentorProfile::STATUS_SUSPENDED) {
            $mail->line('Your mentor profile has been suspended by an administrator. While suspended, your profile will not appear in the directory.');
        }

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mentor_application_status_updated',
            'mentor_profile_id' => $this->mentorProfile->id,
            'status' => $this->mentorProfile->status,
            'reason' => $this->mentorProfile->rejection_reason,
        ];
    }
}
