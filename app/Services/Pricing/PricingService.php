<?php

namespace App\Services\Pricing;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The one place a price is calculated.
 *
 *     unit price = base price
 *                × quantity-break factor
 *                × the account's tier factor
 *                × any negotiated factor on that item for that account
 *
 * Nothing else in the application multiplies money. Controllers, Blade views,
 * the cart and the order writer all come through here, which is what makes
 * "different rates for different customers on the same product" a single rule
 * instead of a scatter of special cases.
 *
 * Note what this class does NOT do: it never decides whether a price may be
 * SHOWN. That is User::canSeePricing() and the pricing gate, checked by the
 * caller. Keeping the two apart means a back-office report can price an item
 * for an account without any of the storefront's visibility rules getting in
 * the way.
 */
class PricingService
{
    /** Override lookups are hot on a category page; memoise per request. */
    private array $overrideCache = [];

    public function quote(Product $product, ?Company $company, int $quantity): Quote
    {
        $quantity = max(1, $quantity);

        $basePrice = (float) $product->base_price;
        $breakFactor = $this->breakFactorFor($quantity);
        $tierFactor = $this->tierFactorFor($company);
        $overrideFactor = $this->overrideFactorFor($product, $company);

        $unitPrice = round($basePrice * $breakFactor * $tierFactor * $overrideFactor, 4);

        return new Quote(
            product: $product,
            company: $company,
            quantity: $quantity,
            basePrice: $basePrice,
            breakFactor: $breakFactor,
            tierFactor: $tierFactor,
            overrideFactor: $overrideFactor,
            unitPrice: $unitPrice,
            unitCost: $product->effectiveCost(),
        );
    }

    public function unitPrice(Product $product, ?Company $company, int $quantity): float
    {
        return $this->quote($product, $company, $quantity)->unitPrice;
    }

    /** The figure shown on a listing card: the price at the item's minimum. */
    public function startingPrice(Product $product, ?Company $company): float
    {
        return $this->unitPrice($product, $company, $product->moq ?: $this->firstBreakQuantity());
    }

    public function quoteFor(Product $product, ?User $user, int $quantity): Quote
    {
        return $this->quote($product, $user?->company, $quantity);
    }

    /* ------------------------------------------------------------ Ladders */

    /**
     * The full quantity ladder for one account, with the row that matches the
     * quantity in hand marked so the item page can highlight it.
     *
     * @return Collection<int, PriceBreak>
     */
    public function ladder(Product $product, ?Company $company, ?int $currentQuantity = null): Collection
    {
        $currentBreak = $currentQuantity !== null
            ? $this->breakQuantityFor($currentQuantity)
            : null;

        return collect(config('tbm.quantity_breaks'))
            ->map(function (array $row) use ($product, $company, $currentBreak) {
                $quantity = (int) $row['quantity'];
                $quote = $this->quote($product, $company, $quantity);

                return new PriceBreak(
                    quantity: $quantity,
                    unitPrice: $quote->unitPrice,
                    factor: (float) $row['factor'],
                    isCurrent: $currentBreak === $quantity,
                );
            })
            ->values();
    }

    /**
     * The same ladder priced for every tier at once — the admin rate-card
     * matrix. Returns tier code => collection of breaks.
     */
    public function matrix(Product $product, Collection $tiers, ?Company $company = null): array
    {
        $matrix = [];

        foreach ($tiers as $tier) {
            $matrix[$tier->code] = collect(config('tbm.quantity_breaks'))->map(function (array $row) use ($product, $tier) {
                $quantity = (int) $row['quantity'];

                return new PriceBreak(
                    quantity: $quantity,
                    unitPrice: round((float) $product->base_price * (float) $row['factor'] * (float) $tier->factor, 4),
                    factor: (float) $row['factor'],
                );
            })->values();
        }

        return $matrix;
    }

    /* ------------------------------------------------------------ Factors */

    /** The factor for the largest break the quantity has reached. */
    public function breakFactorFor(int $quantity): float
    {
        $factor = 1.0;

        foreach (config('tbm.quantity_breaks') as $row) {
            if ($quantity >= (int) $row['quantity']) {
                $factor = (float) $row['factor'];
            }
        }

        return $factor;
    }

    public function breakQuantityFor(int $quantity): int
    {
        $matched = $this->firstBreakQuantity();

        foreach (config('tbm.quantity_breaks') as $row) {
            if ($quantity >= (int) $row['quantity']) {
                $matched = (int) $row['quantity'];
            }
        }

        return $matched;
    }

    /**
     * The next break up, and what reaching it would be worth. This is the
     * "add 60 more and save 7%" prompt on the item page.
     *
     * @return array{quantity:int, unit_price:float, saving_percent:float}|null
     */
    public function nextBreak(Product $product, ?Company $company, int $quantity): ?array
    {
        $current = $this->unitPrice($product, $company, $quantity);

        foreach (config('tbm.quantity_breaks') as $row) {
            if ((int) $row['quantity'] > $quantity) {
                $next = (int) $row['quantity'];
                $price = $this->unitPrice($product, $company, $next);

                return [
                    'quantity' => $next,
                    'shortfall' => $next - $quantity,
                    'unit_price' => $price,
                    'saving_percent' => $current > 0 ? round((1 - $price / $current) * 100, 1) : 0,
                ];
            }
        }

        return null;
    }

    /** A signed-out visitor is quoted the standard card. */
    public function tierFactorFor(?Company $company): float
    {
        return (float) ($company?->tier?->factor ?? 1.0);
    }

    public function overrideFactorFor(Product $product, ?Company $company): float
    {
        if (! $company) {
            return 1.0;
        }

        $key = $company->id.':'.$product->id;

        return $this->overrideCache[$key] ??= (float) (
            $company->priceOverrides()
                ->where('product_id', $product->id)
                ->value('factor') ?? 1.0
        );
    }

    /* -------------------------------------------------------------- Utility */

    public function firstBreakQuantity(): int
    {
        return (int) (config('tbm.quantity_breaks')[0]['quantity'] ?? 1);
    }

    public function breakQuantities(): array
    {
        return array_map(
            fn (array $row) => (int) $row['quantity'],
            config('tbm.quantity_breaks')
        );
    }

    /**
     * Pre-load the overrides for one account so a catalogue page prices a whole
     * result set without a query per card.
     */
    public function warmFor(?Company $company): void
    {
        if (! $company) {
            return;
        }

        foreach ($company->priceOverrides()->get(['product_id', 'factor']) as $override) {
            $this->overrideCache[$company->id.':'.$override->product_id] = (float) $override->factor;
        }
    }

    public function flush(): void
    {
        $this->overrideCache = [];
    }
}
