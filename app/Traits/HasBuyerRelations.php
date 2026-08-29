<?php

namespace App\Traits;

use App\Models\BuyerProfile;
use App\Models\Negotiation;

trait HasBuyerRelations
{
    public function buyerProfile()
    {
        return $this->hasOne(BuyerProfile::class);
    }

    public function buyerNegotiations()
    {
        return $this->hasMany(Negotiation::class, 'buyer_id');
    }
}
