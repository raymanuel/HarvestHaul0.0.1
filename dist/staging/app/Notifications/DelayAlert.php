<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DelayAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;

    public string $message;

    public string $link;

    public function __construct(string $title, string $message, string $link)
    {
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('View Details', url($this->link));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'category' => 'delays',
        ];
    }
}
