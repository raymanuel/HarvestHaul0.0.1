<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'category', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has notifications enabled for a given category.
     * Defaults to enabled (true) if no preference record exists.
     */
    public static function isEnabled(int $userId, string $category): bool
    {
        $pref = static::where('user_id', $userId)->where('category', $category)->first();

        return $pref ? $pref->enabled : true;
    }

    /**
     * Get all preferences for a user, with defaults for missing categories.
     */
    public static function getAllForUser(int $userId): array
    {
        $categories = ['logistics', 'negotiations', 'payments', 'admin', 'weather', 'system', 'delays'];
        $prefs = static::where('user_id', $userId)->pluck('enabled', 'category')->toArray();

        return collect($categories)->mapWithKeys(fn ($cat) => [
            $cat => $prefs[$cat] ?? true,
        ])->toArray();
    }
}
