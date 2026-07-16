<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

trait Notifiable
{
    protected static function sendNotification(int $userId, string $title, string $message, string $link = null, string $type = null, string $category = null): void
    {
        try {
            Notification::create(array_filter([
                'user_id'  => $userId,
                'title'    => $title,
                'message'  => $message,
                'link'     => $link,
                'type'     => $type,
                'category' => $category,
            ], fn($v) => $v !== null));
        } catch (\Exception $e) {
            Log::warning("Failed to send notification to user {$userId}: " . $e->getMessage());
        }
    }

    protected static function logAudit(int $adminId, string $action, string $targetType, int $targetId, string $notes): void
    {
        AuditLog::create([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'notes'       => $notes,
        ]);
    }
}
