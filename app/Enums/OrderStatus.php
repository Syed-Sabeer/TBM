<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Pending confirmation',
            self::Confirmed => 'Confirmed',
            self::InProduction => 'In production',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Delivered => 'st-delivered',
            self::Shipped => 'st-shipped',
            self::InProduction, self::Confirmed => 'st-production',
            self::PendingConfirmation => 'st-pending',
            self::Cancelled => 'st-cancelled',
        };
    }

    /** Position on the customer-facing progress timeline. */
    public function step(): int
    {
        return match ($this) {
            self::PendingConfirmation => 1,
            self::Confirmed => 2,
            self::InProduction => 3,
            self::Shipped => 4,
            self::Delivered => 5,
            self::Cancelled => 0,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::PendingConfirmation,
            self::Confirmed,
            self::InProduction,
            self::Shipped,
        ], true);
    }

    public function countsTowardRevenue(): bool
    {
        return $this !== self::Cancelled;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
