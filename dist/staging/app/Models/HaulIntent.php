<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HaulIntent extends Model
{
    protected $fillable = [
        'haul_request_id',
        'logistics_profile_id',
        'notes',
        'suggested_date',
        'offer_rate_php_per_kg',
        'counter_rate_php_per_kg',
        'hauling_rate_php_per_kg',
        'status',
    ];

    protected $casts = [
        'suggested_date' => 'date',
    ];

    public function haulRequest(): BelongsTo
    {
        return $this->belongsTo(HaulRequest::class);
    }

    public function logisticsProfile(): BelongsTo
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(HaulIntentMessage::class);
    }

    /** The currently active proposed rate: logistics offer, or farmer counter if set. */
    public function currentRate(): ?float
    {
        return (float) ($this->counter_rate_php_per_kg ?? $this->offer_rate_php_per_kg) ?: null;
    }
}
