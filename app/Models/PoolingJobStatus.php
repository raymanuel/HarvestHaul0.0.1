<?php

namespace App\Models;

enum PoolingJobStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_CONFIRMATION = 'awaiting_confirmation';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING               => 'Pending',
            self::CONFIRMED             => 'Confirmed',
            self::IN_PROGRESS           => 'In Transit',
            self::AWAITING_CONFIRMATION => 'Awaiting Confirmation',
            self::COMPLETED             => 'Completed',
            self::CANCELLED             => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING               => 'yellow',
            self::CONFIRMED             => 'blue',
            self::IN_PROGRESS           => 'orange',
            self::AWAITING_CONFIRMATION => 'amber',
            self::COMPLETED             => 'green',
            self::CANCELLED             => 'red',
        };
    }
}
