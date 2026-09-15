<?php

namespace App\Enums;

enum StaffRole: string
{
    case Owner = 'owner';
    case AccountManager = 'account-manager';
    case Inventory = 'inventory';
    case CustomerCare = 'customer-care';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::AccountManager => 'Account Manager',
            self::Inventory => 'Quality & Inventory',
            self::CustomerCare => 'Customer Care',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
