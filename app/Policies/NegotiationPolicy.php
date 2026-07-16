<?php

namespace App\Policies;

use App\Models\Negotiation;
use App\Models\User;

class NegotiationPolicy
{
    /**
     * Who can view/access a negotiation (room, messages, list):
     * - The buyer involved
     * - The farmer involved
     * - Admins
     */
    public function view(User $user, Negotiation $negotiation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $negotiation->buyer_id === $user->id
            || $negotiation->farmer_id === $user->id;
    }

    /**
     * Who can update a negotiation (propose terms, agree, send messages):
     * - The buyer involved
     * - The farmer involved
     * (Status-specific checks remain in the controller)
     */
    public function update(User $user, Negotiation $negotiation): bool
    {
        return $negotiation->buyer_id === $user->id
            || $negotiation->farmer_id === $user->id;
    }
}
