<?php

namespace App\Policies;

use App\Models\PoolingJob;
use App\Models\User;

class DriverPolicy
{
    /**
     * Only the assigned driver can view the job.
     */
    public function view(User $user, PoolingJob $job): bool
    {
        return $job->driver_id === $user->id;
    }

    /**
     * Only the assigned driver can update status or stop status.
     */
    public function update(User $user, PoolingJob $job): bool
    {
        return $job->driver_id === $user->id;
    }

    /**
     * Only the assigned driver can log fuel for the job's truck.
     */
    public function logFuel(User $user, PoolingJob $job): bool
    {
        return $job->driver_id === $user->id;
    }
}
