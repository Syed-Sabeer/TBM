<?php

namespace App\Services\Pricing;

use App\Models\Company;
use App\Models\Product;

/**
 * The answer to "what does this account pay for this item at this quantity",
 * with every factor that produced it kept alongside the number.
 *
 * Holding the working rather than just the result is deliberate: the back
 * office has to be able to explain a price to a customer on the phone, and the
 * margin check needs the cost that was in force when the figure was struck.
 */
class Quote
{
    public function __construct(
        public readonly Product $product,
        public readonly ?Company $company,
        public readonly int $quantity,
        public readonly float $basePrice,
        public readonly float $breakFactor,
        public readonly float $tierFactor,
        public readonly float $overrideFactor,
        public readonly float $unitPrice,
        public readonly float $unitCost,
    ) {
    }

    public function lineTotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    /** The list price at this quantity, before the account's own terms. */
    public function listPrice(): float
    {
        return round($this->basePrice * $this->breakFactor, 4);
    }

    public function savingAgainstList(): float
    {
        return round($this->listPrice() - $this->unitPrice, 4);
    }

    public function savingPercent(): float
    {
        $list = $this->listPrice();

        return $list > 0 ? round((1 - $this->unitPrice / $list) * 100, 1) : 0;
    }

    /* -------------------------------------------------------- Back office */

    public function marginPercent(): float
    {
        if ($this->unitPrice <= 0) {
            return 0;
        }

        return round(($this->unitPrice - $this->unitCost) / $this->unitPrice * 100, 1);
    }

    public function isBelowMarginFloor(): bool
    {
        return $this->marginPercent() < (float) config('tbm.margin_floor');
    }

    public function hasNegotiatedRate(): bool
    {
        return abs($this->overrideFactor - 1.0) > 0.0001;
    }

    /**
     * How the number was reached, in the order the factors are applied. Used
     * by the admin price explainer.
     */
    public function workings(): array
    {
        return [
            ['label' => 'Base price', 'value' => $this->basePrice, 'type' => 'money'],
            ['label' => sprintf('Quantity break at %s', number_format($this->quantity)), 'value' => $this->breakFactor, 'type' => 'factor'],
            ['label' => $this->company?->tier?->fullName() ?? 'Standard card', 'value' => $this->tierFactor, 'type' => 'factor'],
            ['label' => 'Negotiated rate on this item', 'value' => $this->overrideFactor, 'type' => 'factor'],
            ['label' => 'Unit price', 'value' => $this->unitPrice, 'type' => 'money'],
        ];
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->product->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal(),
            'list_price' => $this->listPrice(),
            'saving_percent' => $this->savingPercent(),
        ];
    }
}
