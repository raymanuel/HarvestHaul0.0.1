<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HaulIntentMessage extends Model
{
    protected $fillable = [
        'haul_intent_id',
        'sender_id',
        'message_text',
    ];

    public function haulIntent(): BelongsTo
    {
        return $this->belongsTo(HaulIntent::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
