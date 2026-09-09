<?php

namespace App\Policies;

use App\Models\Harvest;
use App\Models\User;

class HarvestPolicy
{
    /**
     * Only the farmer who owns the harvest can view it.
     */
    public function view(User $user, Harvest $harvest): bool
    {
        return $user->id === $harvest->user_id || $user->role === 'admin';
    }

    /**
     * Only the farmer who owns the harvest can update it.
     */
    public function update(User $user, Harvest $harvest): bool
    {
        return $user->id === $harvest->user_id || $user->role === 'admin';
    }

    /**
     * Only the farmer who owns the harvest can delete it.
     */
    public function delete(User $user, Harvest $harvest): bool
    {
        return $user->id === $harvest->user_id || $user->role === 'admin';
    }

    /**
     * Farmers with verified profiles can create harvests.
     */
    public function create(User $user): bool
    {
        return $user->role === 'farmer'
            && $user->farmerProfile
            && $user->farmerProfile->is_verified;
    }
}
