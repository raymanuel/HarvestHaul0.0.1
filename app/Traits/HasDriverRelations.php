<?php

namespace App\Traits;

use App\Models\DriverProfile;

trait HasDriverRelations
{
    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function assignedHaulJobs()
    {
        return $this->hasMany(\App\Models\HaulJob::class, 'delivery_personnel_id');
    }

    public function assignedDeliveries()
    {
        return $this->hasMany(\App\Models\Delivery::class, 'delivery_personnel_id');
    }
}