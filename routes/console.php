<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email? : The recipient email address for testing}', function (?string $email = null) {
    $recipient = $email ?? config('mail.from.address');

    $validator = \Illuminate\Support\Facades\Validator::make(['email' => $recipient], [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        $this->error('Invalid recipient email address: '.$recipient);
        return 1;
    }

    $mailer = config('mail.default');
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $from = config('mail.from.address');

    $this->info('Testing mail configuration...');
    $this->line("  Mailer:    {$mailer}");
    if ($mailer === 'smtp') {
        $this->line("  Host:      {$host}");
        $this->line("  Port:      {$port}");
    }
    $this->line("  From:      {$from}");
    $this->line("  Recipient: {$recipient}");

    try {
        \Illuminate\Support\Facades\Mail::raw("This is a test email sent from BUP ICT Alumni Association Platform to verify SMTP connectivity.\n\nSent at: ".now()->toIso8601String(), function ($message) use ($recipient) {
            $message->to($recipient)
                ->subject('BUP ICT Alumni Platform — SMTP Connectivity Test');
        });

        $this->info("✓ Test email sent successfully to {$recipient} via [{$mailer}] transport.");
        return 0;
    } catch (\Throwable $e) {
        $this->error('✗ Mail delivery failed: '.$e->getMessage());
        $this->line('  Exception: '.get_class($e));
        $this->line('  Check your .env mail configuration, Google App Password, and network connectivity.');
        return 1;
    }
})->purpose('Send a test email using the currently configured mail transport');
