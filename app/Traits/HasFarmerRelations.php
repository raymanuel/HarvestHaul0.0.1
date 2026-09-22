<?php

namespace App\Traits;

use App\Models\FarmerProfile;
use App\Models\HaulRequest;
use App\Models\FarmerDocument;

trait HasFarmerRelations
{
    public function farmerProfile()
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function farmerDocuments()
    {
        return $this->hasMany(FarmerDocument::class, 'user_id');
    }
}