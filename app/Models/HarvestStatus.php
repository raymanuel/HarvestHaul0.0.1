<?php

namespace App\Models;

enum HarvestStatus: string
{
    case ACTIVE = 'active';
    case NEGOTIATING = 'negotiating';
    case PARTIALLY_SOLD = 'partially_sold';
    case SOLD = 'sold';
    case BOOKED = 'booked';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /** Statuses where the harvest is visible to buyers for negotiation. */
    public static function buyerAvailable(): array
    {
        return [self::ACTIVE, self::PARTIALLY_SOLD];
    }

    /** Statuses where the harvest is visible to logistics partners. */
    public static function logisticsVisible(): array
    {
        return [self::ACTIVE, self::NEGOTIATING, self::PARTIALLY_SOLD, self::SOLD, self::BOOKED];
    }

    /** Statuses where the harvest is locked (no further edits by the farmer). */
    public static function locked(): array
    {
        return [self::NEGOTIATING, self::SOLD, self::BOOKED, self::ASSIGNED, self::IN_PROGRESS, self::COMPLETED];
    }

    /** Check if this specific status is locked. */
    public function isLocked(): bool
    {
        return in_array($this, self::locked());
    }

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE         => 'Active',
            self::NEGOTIATING    => 'Under Negotiation',
            self::PARTIALLY_SOLD => 'Partially Sold',
            self::SOLD           => 'Sold',
            self::BOOKED         => 'Booked',
            self::ASSIGNED       => 'Assigned',
            self::IN_PROGRESS    => 'In Transit',
            self::COMPLETED      => 'Completed',
            self::CANCELLED      => 'Cancelled',
        };
    }

    /** Tailwind color class for badges. */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE         => 'green',
            self::NEGOTIATING    => 'yellow',
            self::PARTIALLY_SOLD => 'blue',
            self::SOLD           => 'indigo',
            self::BOOKED         => 'purple',
            self::ASSIGNED       => 'purple',
            self::IN_PROGRESS    => 'orange',
            self::COMPLETED      => 'gray',
            self::CANCELLED      => 'red',
        };
    }
}
