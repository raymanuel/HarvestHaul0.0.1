<?php

namespace App\Services;

use App\Models\NotificationPreference;

class NotificationPreferenceService
{
    public function isEnabled(int $userId, string $category): bool
    {
        $pref = NotificationPreference::where('user_id', $userId)
            ->where('category', $category)
            ->first();

        return $pref ? $pref->enabled : true;
    }

    public function getAllForUser(int $userId): array
    {
        $categories = ['logistics', 'negotiations', 'payments', 'admin', 'weather', 'system', 'delays'];
        $prefs = NotificationPreference::where('user_id', $userId)
            ->pluck('enabled', 'category')
            ->toArray();

        return collect($categories)->mapWithKeys(fn ($cat) => [
            $cat => $prefs[$cat] ?? true,
        ])->toArray();
    }
}
