<?php

namespace App\Models;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case VOIDED = 'voided';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT   => 'Draft',
            self::SENT    => 'Sent',
            self::PAID    => 'Paid',
            self::VOIDED  => 'Voided',
            self::OVERDUE => 'Overdue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT   => 'gray',
            self::SENT    => 'blue',
            self::PAID    => 'green',
            self::VOIDED  => 'red',
            self::OVERDUE => 'orange',
        };
    }
}
