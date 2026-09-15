<?php

namespace App\Services\Cart;

/**
 * Basket totals. Decoration and tax are absent on purpose: decoration is
 * quoted by a rep after artwork, and tax depends on the resale certificate on
 * file, so neither can honestly be shown before the order is acknowledged.
 */
class CartTotals
{
    public function __construct(
        public readonly float $merchandise,
        public readonly float $freight,
        public readonly int $pieces,
        public readonly int $lineCount,
    ) {
    }

    public function estimatedTotal(): float
    {
        return round($this->merchandise + $this->freight, 2);
    }

    public function averageUnitPrice(): float
    {
        return $this->pieces > 0 ? round($this->merchandise / $this->pieces, 4) : 0;
    }

    public function qualifiesForFreeFreight(): bool
    {
        return $this->merchandise >= (float) config('tbm.storefront.free_freight_over');
    }

    public function freeFreightShortfall(): float
    {
        return max(0, (float) config('tbm.storefront.free_freight_over') - $this->merchandise);
    }

    public function requiresPurchaseOrder(): bool
    {
        return $this->merchandise >= (float) config('tbm.storefront.require_po_over');
    }
}
