<?php

namespace App\Policies;

use App\Models\PoolingJob;
use App\Models\User;

class PoolingJobPolicy
{
    /**
     * Who can view a pooling job:
     * - The logistics partner who owns it
     * - Any farmer with a harvest in the job
     * - The buyer assigned to the job
     * - The driver assigned to the job
     * - Admins
     */
    public function view(User $user, PoolingJob $job): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->logisticsProfile && $user->logisticsProfile->id === $job->logistics_profile_id) {
            return true;
        }

        if ($job->harvests->contains('user_id', $user->id)) {
            return true;
        }

        if ($job->buyer_id && $job->buyer_id === $user->id) {
            return true;
        }

        if ($job->driver_id && $job->driver_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Who can update/manage a pooling job (confirm, counter, accept):
     * Only the logistics partner who owns it.
     */
    public function update(User $user, PoolingJob $job): bool
    {
        return $user->logisticsProfile
            && $user->logisticsProfile->id === $job->logistics_profile_id;
    }

    /**
     * Who can manage harvests within a pooling job (confirm received, mark loaded):
     * The logistics partner, the assigned driver, or admins.
     */
    public function manageHarvests(User $user, PoolingJob $job): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->logisticsProfile && $user->logisticsProfile->id === $job->logistics_profile_id) {
            return true;
        }

        if ($job->driver_id && $job->driver_id === $user->id) {
            return true;
        }

        return false;
    }
}
