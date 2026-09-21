<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    public const CONTEXTS = ['haul_request', 'buyer_order', 'pickup_trip', 'delivery'];

    protected $fillable = [
        'sender_id', 'recipient_id', 'cooperative_id', 'body', 'read_at',
        'context_type', 'context_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    /** Human-readable label for the message's context reference, if any. */
    public function contextLabel(): ?string
    {
        if (! $this->context_type || ! $this->context_id) {
            return null;
        }

        return match ($this->context_type) {
            'haul_request' => 'Haul Request #'.$this->context_id,
            'buyer_order'  => 'Order #'.$this->context_id,
            'pickup_trip'  => 'Pickup Trip #'.$this->context_id,
            'delivery'     => 'Delivery #'.$this->context_id,
            default        => null,
        };
    }

    public function scopeForUser($query, $userId, $otherUserId)
    {
        return $query->where(fn ($q) => $q
            ->where('sender_id', $userId)
            ->where('recipient_id', $otherUserId))
            ->orWhere(fn ($q) => $q
                ->where('sender_id', $otherUserId)
                ->where('recipient_id', $userId));
    }
}