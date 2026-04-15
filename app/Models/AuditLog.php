<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'notes',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    // Relationship: An AuditLog BELONGS TO a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
