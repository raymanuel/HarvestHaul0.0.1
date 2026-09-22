<?php

namespace App\Traits;

use App\Models\LogisticsProfile;

trait HasLogisticsRelations
{
    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }
}
