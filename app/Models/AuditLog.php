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

    // The admin who performed the action
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // -------------------------------------------------------
    // target() removed — target_id is a generic reference ID
    // across multiple tables (users, crops, crop_categories,
    // crop_varieties). Cannot be FK-constrained to User only.
    //
    // user() removed — redundant with admin(). AuditLog has
    // no implicit user_id column.
    //
    // Use notes for human-readable context.
    // Use target_type + target_id together if programmatic
    // resolution is needed in the future.
    // -------------------------------------------------------
}
