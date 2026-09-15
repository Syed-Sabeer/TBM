<?php

namespace App\Enums;

/**
 * What a login may DO inside its company account. It never affects what the
 * company is CHARGED — price comes from the account, not the person.
 */
enum CompanyUserRole: string
{
    case Admin = 'customer-admin';
    case Buyer = 'customer-buyer';
    case Viewer = 'customer-viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Buyer => 'Buyer',
            self::Viewer => 'View only',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything a Buyer can do, plus inviting users, editing addresses and changing company details.',
            self::Buyer => 'Sees pricing, places and reorders orders, downloads invoices and reports.',
            self::Viewer => 'Sees pricing, order history and documents. Cannot submit an order.',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
