<?php

namespace App\Traits;

use App\Models\FarmerProfile;
use App\Models\Harvest;
use App\Models\HaulRequest;
use App\Models\FarmerDocument;
use App\Models\Negotiation;

trait HasFarmerRelations
{
    public function farmerProfile()
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'user_id');
    }

    public function assignedHarvests()
    {
        return $this->hasMany(Harvest::class, 'driver_id');
    }

    public function haulRequests()
    {
        return $this->hasMany(HaulRequest::class, 'user_id');
    }

    public function farmerDocuments()
    {
        return $this->hasMany(FarmerDocument::class, 'user_id');
    }

    public function farmerNegotiations()
    {
        return $this->hasMany(Negotiation::class, 'farmer_id');
    }
}
