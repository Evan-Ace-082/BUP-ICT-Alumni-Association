<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $expiryMinutes = 5,
    ) {
        $this->subject = 'BUP ICT Alumni Association - Password Reset Verification';
    }

    public function build(): self
    {
        return $this->subject($this->subject)
            ->from(config('mail.from.address'), config('mail.from.name', 'BUP ICT Alumni Association'))
            ->view('emails.password-reset-otp');
    }
}
