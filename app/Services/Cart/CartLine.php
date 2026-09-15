<?php

namespace App\Services\Cart;

use App\Models\Colourway;
use App\Models\Product;
use App\Services\Pricing\Quote;

/**
 * A resolved basket line: the stored selection, the live models it points at,
 * and the price it carries at its current quantity.
 *
 * A line is rebuilt from the session on every request rather than cached, so a
 * rate-card change, a tier move or a stock correction is reflected the moment
 * the customer reloads. Money is only frozen when the order is written.
 */
class CartLine
{
    public function __construct(
        public readonly string $key,
        public readonly Product $product,
        public readonly ?Colourway $colourway,
        public readonly string $size,
        public readonly string $decoration,
        public readonly int $quantity,
        public readonly Quote $quote,
    ) {
    }

    public function unitPrice(): float
    {
        return $this->quote->unitPrice;
    }

    public function lineTotal(): float
    {
        return round($this->unitPrice() * $this->quantity, 2);
    }

    public function colourName(): string
    {
        return $this->colourway?->name ?? 'As shown';
    }

    public function isDecorated(): bool
    {
        return ! str_starts_with($this->decoration, 'Blank');
    }

    public function meetsMinimum(): bool
    {
        return $this->quantity >= $this->product->moq;
    }

    /** Stock available for this line, across every site the account may use. */
    public function availableStock(): int
    {
        return $this->product->totalStock();
    }

    public function isBackordered(): bool
    {
        return $this->quantity > $this->availableStock();
    }

    /**
     * Anything the customer should be told about this line before checkout.
     *
     * @return array<int, array{level:string, message:string}>
     */
    public function warnings(): array
    {
        $warnings = [];

        if (! $this->meetsMinimum()) {
            $warnings[] = [
                'level' => 'warn',
                'message' => sprintf(
                    'The minimum on %s is %s pieces.',
                    $this->product->sku,
                    number_format($this->product->moq)
                ),
            ];
        }

        if ($this->isBackordered()) {
            $short = $this->quantity - $this->availableStock();
            $warnings[] = [
                'level' => 'warn',
                'message' => sprintf(
                    '%s pieces of %s are beyond current stock and will follow on backorder.',
                    number_format($short),
                    $this->product->sku
                ),
            ];
        }

        if ($this->isDecorated()) {
            $warnings[] = [
                'level' => 'info',
                'message' => sprintf('%s on this line is quoted separately once artwork is approved.', $this->decoration),
            ];
        }

        return $warnings;
    }

    /** What the order writer copies onto the line item. */
    public function toOrderItemAttributes(): array
    {
        return [
            'product_id' => $this->product->id,
            'colourway_id' => $this->colourway?->id,
            'sku' => $this->product->sku,
            'parent_sku' => $this->product->parent_sku,
            'name' => $this->product->name,
            'colour_name' => $this->colourName(),
            'size' => $this->size,
            'decoration' => $this->decoration,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice(),
            'unit_cost' => $this->product->effectiveCost(),
            'line_total' => $this->lineTotal(),
        ];
    }
}
