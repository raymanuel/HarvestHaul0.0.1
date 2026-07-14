<?php

namespace App\Models;

class HarvestStatus
{
    const PENDING = 'pending';
    const ACTIVE = 'active';
    const NEGOTIATING = 'negotiating';
    const PARTIALLY_SOLD = 'partially_sold';
    const SOLD = 'sold';
    const ASSIGNED = 'assigned';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    const BUYER_AVAILABLE = [self::ACTIVE, self::PARTIALLY_SOLD];
    const LOGISTICS_VISIBLE = [self::SOLD, self::PARTIALLY_SOLD];
    const LOCKED = [self::NEGOTIATING, self::PARTIALLY_SOLD, self::SOLD, self::ASSIGNED, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];
}
