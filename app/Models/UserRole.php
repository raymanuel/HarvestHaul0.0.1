<?php

namespace App\Models;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case COOP_ADMIN = 'coop_admin';
    case FIELD_RECEIVING = 'field_receiving';
    case DELIVERY_PERSONNEL = 'delivery_personnel';
    case FARMER = 'farmer';
    case BUYER = 'buyer';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::COOP_ADMIN => 'Cooperative Admin',
            self::FIELD_RECEIVING => 'Field / Receiving Personnel',
            self::DELIVERY_PERSONNEL => 'Delivery Personnel',
            self::FARMER => 'Farmer',
            self::BUYER => 'Buyer',
        };
    }
}