<?php

namespace App\Models;

final class PoolingJobStatus
{
    public const PENDING     = 'planned';
    public const CONFIRMED   = 'confirmed';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELLED   = 'cancelled';

    public const PLANNED   = self::PENDING;
    public const CONFIRMING = self::CONFIRMED;
}
