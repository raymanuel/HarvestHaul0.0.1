<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteCalculation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider', 'profile', 'kind', 'coordinates_hash',
        'result', 'calculated_at', 'expires_at',
    ];

    protected $casts = [
        'result' => 'array',
        'calculated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Named to avoid colliding with Eloquent's own instance method
    // Model::fresh() — ::query()->notExpired() reads correctly either way.
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
