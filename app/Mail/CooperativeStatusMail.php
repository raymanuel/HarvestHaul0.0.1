<?php

namespace App\Mail;

use App\Models\Cooperative;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CooperativeStatusMail extends Mailable
{
    use Queueable;

    public Cooperative $cooperative;
    public string $status;
    public ?string $reason;
    public ?string $actorName;

    /**
     * @param string $status approved|rejected|request_info|suspended|resubmitted|reactivated
     */
    public function __construct(
        Cooperative $cooperative,
        string $status,
        ?string $reason = null,
        ?string $actorName = null
    ) {
        $this->cooperative = $cooperative;
        $this->status = $status;
        $this->reason = $reason;
        $this->actorName = $actorName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForStatus(),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.cooperative-status',
        );
    }

    private function subjectForStatus(): string
    {
        return match ($this->status) {
            'approved' => "Your cooperative {$this->cooperative->name} was approved — the workspace is open",
            'rejected' => "Update on your cooperative application: {$this->cooperative->name}",
            'request_info' => "We need more information for {$this->cooperative->name}",
            'suspended' => "Notice: {$this->cooperative->name} has been suspended",
            'reactivated' => "Good news: {$this->cooperative->name} has been reactivated",
            default => "Update on your cooperative application: {$this->cooperative->name}",
        };
    }
}
