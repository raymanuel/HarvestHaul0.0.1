<?php

namespace App\Channels;

use App\Models\Notification;
use Illuminate\Notifications\Notification as NotificationClass;

class DatabaseChannel
{
    public function send($notifiable, NotificationClass $notification): void
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : $notification->toArray($notifiable);

        Notification::create([
            'user_id' => $notifiable->id,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'link' => $data['link'] ?? null,
            'type' => $data['type'] ?? get_class($notification),
            'category' => $data['category'] ?? null,
        ]);
    }
}
