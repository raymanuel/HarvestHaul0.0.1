<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerExpense extends Model
{
    public const CATEGORIES = ['seeds', 'fertilizer', 'pesticide', 'labor', 'transport', 'other'];

    protected $fillable = [
        'user_id',
        'crop_id',
        'category',
        'description',
        'amount',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
