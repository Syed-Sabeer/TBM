<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Draft = 'draft';
    case Previewed = 'previewed';
    case Applied = 'applied';
    case AppliedWithWarnings = 'applied_with_warnings';
    case RolledBack = 'rolled_back';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Previewed => 'Awaiting approval',
            self::Applied => 'Applied',
            self::AppliedWithWarnings => 'Applied with warnings',
            self::RolledBack => 'Rolled back',
            self::Failed => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Applied => 'badge-ok',
            self::AppliedWithWarnings => 'badge-warn',
            self::Failed, self::RolledBack => 'badge-bad',
            default => 'badge',
        };
    }
}
