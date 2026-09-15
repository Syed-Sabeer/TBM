<?php

namespace App\Services\Pricing;

/**
 * One row of a quantity ladder: the quantity it starts at, the unit price at
 * that quantity, and how far under the smallest break it sits.
 */
class PriceBreak
{
    public function __construct(
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $factor,
        public readonly bool $isCurrent = false,
    ) {
    }

    public function lineTotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    public function savingPercentAgainst(float $referencePrice): float
    {
        if ($referencePrice <= 0) {
            return 0;
        }

        return round((1 - $this->unitPrice / $referencePrice) * 100, 1);
    }

    public function withCurrent(bool $isCurrent): self
    {
        return new self($this->quantity, $this->unitPrice, $this->factor, $isCurrent);
    }

    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'factor' => $this->factor,
            'is_current' => $this->isCurrent,
        ];
    }
}
