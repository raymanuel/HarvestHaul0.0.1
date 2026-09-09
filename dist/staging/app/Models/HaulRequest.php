<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HaulRequest extends Model
{
    protected $fillable = [
        'harvest_id',
        'user_id',
        'buyer_id',
        'negotiation_id',
        'pickup_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'pickup_date' => 'date',
    ];

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(Negotiation::class, 'negotiation_id');
    }

    public function intents(): HasMany
    {
        return $this->hasMany(HaulIntent::class);
    }

    public function acceptedIntent()
    {
        return $this->hasOne(HaulIntent::class)->where('status', 'accepted');
    }
}
