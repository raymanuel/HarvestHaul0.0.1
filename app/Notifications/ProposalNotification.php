<?php

namespace App\Notifications;

use App\Models\PoolingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProposalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PoolingJob $job;

    public string $type;

    public string $message;

    public function __construct(PoolingJob $job, string $type, string $message)
    {
        $this->job = $job;
        $this->type = $type;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->type) {
            'new_proposal' => "New Pooling Proposal — Route #{$this->job->id}",
            'proposal_accepted' => "Proposal Accepted — Route #{$this->job->id}",
            'proposal_rejected' => "Proposal Rejected — Route #{$this->job->id}",
            default => "Proposal Update — Route #{$this->job->id}",
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($this->message)
            ->action('View Details', url("/pooling-jobs/{$this->job->id}"));
    }

    public function toArray($notifiable): array
    {
        $title = match ($this->type) {
            'new_proposal' => 'New Pooling Proposal',
            'proposal_accepted' => 'Proposal Accepted',
            'proposal_rejected' => 'Proposal Rejected',
            default => 'Proposal Update',
        };

        return [
            'title' => "{$title} — Route #{$this->job->id}",
            'message' => $this->message,
            'link' => "/pooling-jobs/{$this->job->id}",
            'category' => 'logistics',
        ];
    }
}
