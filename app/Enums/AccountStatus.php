<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending approval',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Closed => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'st-delivered',
            self::OnHold, self::Closed => 'st-cancelled',
            self::PendingApproval => 'st-production',
        };
    }

    /** Only an active account may see pricing or place an order. */
    public function canTrade(): bool
    {
        return $this === self::Active;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
