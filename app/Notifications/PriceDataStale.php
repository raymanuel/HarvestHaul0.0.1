<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PriceDataStale extends Notification
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

    // Intentionally NOT ShouldQueue: this alert must fire even when no queue worker is
    // running. Database comes first so the notification persists even if SMTP fails.
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('View Market Prices', $this->link);
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'category' => 'prices',
        ];
    }
}
