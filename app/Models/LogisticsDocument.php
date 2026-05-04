<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsDocument extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'original_filename',
        'status',
        'business_permit_match_confirmed',
        'notes',
    ];

    protected $casts = [
        'business_permit_match_confirmed' => 'boolean',
    ];

    public function logisticsPartner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
