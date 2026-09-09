<?php

namespace App\Traits;

use App\Models\DriverProfile;
use App\Models\FuelLog;

trait HasDriverRelations
{
    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class, 'driver_id');
    }
}
