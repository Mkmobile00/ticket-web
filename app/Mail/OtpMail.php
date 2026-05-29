<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Generic one-time-code email (password reset, email verification). */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $heading = 'Your verification code',
        public string $intro = 'Use the code below to continue:',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading . ' — ' . config('app.name', 'Boleto'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'code' => $this->code,
            'heading' => $this->heading,
            'intro' => $this->intro,
        ]);
    }
}
