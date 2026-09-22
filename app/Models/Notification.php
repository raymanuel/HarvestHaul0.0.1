<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\NotificationPreferenceService;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'read_at',
        'type',
        'category',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $notification) {
            if (! $notification->category) {
                return true;
            }

            return app(NotificationPreferenceService::class)->isEnabled($notification->user_id, $notification->category);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
