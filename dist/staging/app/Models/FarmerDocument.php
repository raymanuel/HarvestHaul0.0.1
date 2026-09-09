<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerDocument extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'original_filename',
        'status',
        'notes',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
