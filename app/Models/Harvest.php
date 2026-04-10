<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Harvest extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'crop_type',
        'quantity_kg',
        'status',
        'notes',
    ];
    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    // The Farmer who posted this harvest listing
    public function farmer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // The Driver assigned to pick up this harvest (nullable)
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // -------------------------------------------------------
    // Scopes — clean query shortcut controllers
    // -------------------------------------------------------

    // Harvest::active()->get()
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Harvest::pending()->get()
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Harvest::unassigned()->get()
    public function scopeUnassigned($query)
    {
        return $query->whereNull('driver_id');
    }
}
