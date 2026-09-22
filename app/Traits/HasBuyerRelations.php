<?php

namespace App\Traits;

use App\Models\BuyerProfile;

trait HasBuyerRelations
{
    public function buyerProfile()
    {
        return $this->hasOne(BuyerProfile::class);
    }
}