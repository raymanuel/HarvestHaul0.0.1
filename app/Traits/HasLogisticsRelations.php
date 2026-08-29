<?php

namespace App\Traits;

use App\Models\LogisticsProfile;
use App\Models\LogisticsDocument;

trait HasLogisticsRelations
{
    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }

    public function logisticsDocuments()
    {
        return $this->hasMany(LogisticsDocument::class, 'user_id');
    }
}
