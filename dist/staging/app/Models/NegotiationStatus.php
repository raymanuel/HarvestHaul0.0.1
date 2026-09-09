<?php

namespace App\Models;

enum NegotiationStatus: string
{
    case OPEN = 'OPEN';
    case AGREED = 'AGREED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN      => 'Open',
            self::AGREED    => 'Agreed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN      => 'blue',
            self::AGREED    => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
