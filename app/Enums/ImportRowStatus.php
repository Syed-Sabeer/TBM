<?php

namespace App\Enums;

enum ImportRowStatus: string
{
    case Ready = 'ready';
    case NewColourway = 'new_colourway';
    case Unmatched = 'unmatched';
    case Skipped = 'skipped';
    case Applied = 'applied';

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'Ready',
            self::NewColourway => 'New colour',
            self::Unmatched => 'No match',
            self::Skipped => 'Skipped',
            self::Applied => 'Applied',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this, [self::NewColourway, self::Unmatched], true);
    }
}
