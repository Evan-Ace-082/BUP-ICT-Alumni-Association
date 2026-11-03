<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $expiryMinutes = 10,
    ) {}

    public function build(): self
    {
        return $this->subject('Your BUP ICT Alumni Admin 2FA Verification Code')
            ->from(config('mail.from.address'), config('mail.from.name', 'BUP ICT Alumni Association'))
            ->view('emails.admin-login-otp');
    }
}
