<?php

namespace App\Support;

/**
 * Money and number formatting, in one place so the storefront, the portal and
 * every document agree on how a figure looks.
 */
class Money
{
    /** $1,240.00 */
    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return '$'.number_format((float) $amount, $decimals);
    }

    /**
     * Unit prices are quoted to four places in the trade — $1.8725 a piece is a
     * real difference at 25,000 pieces — but trailing zeros read as noise.
     */
    public static function unit(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = round($amount, 2) === round($amount, 4) ? 2 : 4;

        return self::format($amount, $decimals);
    }

    /** $1.2M / $84.5k / $940 — for dashboard tiles, never for documents. */
    public static function compact(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return match (true) {
            abs($amount) >= 1_000_000 => '$'.number_format($amount / 1_000_000, 1).'M',
            abs($amount) >= 1_000 => '$'.number_format($amount / 1_000, 1).'k',
            default => '$'.number_format($amount),
        };
    }

    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals);
    }

    public static function compactNumber(float|int|string|null $value): string
    {
        $value = (float) $value;

        return match (true) {
            abs($value) >= 1_000_000 => number_format($value / 1_000_000, 1).'M',
            abs($value) >= 1_000 => number_format($value / 1_000, 1).'k',
            default => number_format($value),
        };
    }

    /** "+12.4%" / "−3.1%" / "—" when there is no honest base to compare with. */
    public static function delta(?float $percent, int $decimals = 1): string
    {
        if ($percent === null) {
            return '—';
        }

        $sign = $percent > 0 ? '+' : ($percent < 0 ? '−' : '');

        return $sign.number_format(abs($percent), $decimals).'%';
    }
}
