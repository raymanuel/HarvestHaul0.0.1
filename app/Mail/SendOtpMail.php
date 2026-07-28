<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SendOtpMail extends Mailable
{

    public string $otp;
    public string $userName;

    public function __construct(string $otp, string $userName)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your HarvestHaul Email Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.send-otp',
        );
    }
}
