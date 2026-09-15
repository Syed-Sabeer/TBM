<?php

namespace App\Services\Cart;

use App\Models\Colourway;
use App\Models\Company;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The basket.
 *
 * The session stores selections only — product, colour, size, decoration and
 * quantity. Not one figure of money is kept there, so a stale basket can never
 * carry a stale price into an order, and a customer cannot tamper their way to
 * a better rate. Prices are recomputed through PricingService on every read.
 */
class CartService
{
    private const SESSION_KEY = 'tbm.cart';

    /** @var Collection<int, CartLine>|null */
    private ?Collection $resolved = null;

    private ?Company $pricedFor = null;

    public function __construct(
        private readonly Session $session,
        private readonly PricingService $pricing,
    ) {
    }

    /* ---------------------------------------------------------- Mutation */

    public function add(
        Product $product,
        ?Colourway $colourway,
        string $size,
        string $decoration,
        int $quantity,
    ): string {
        $quantity = $this->normaliseQuantity($product, $quantity);
        $key = $this->keyFor($product, $colourway, $size, $decoration);

        $lines = $this->raw();

        if (isset($lines[$key])) {
            $lines[$key]['quantity'] = $this->normaliseQuantity(
                $product,
                $lines[$key]['quantity'] + $quantity
            );
        } else {
            $lines[$key] = [
                'product_id' => $product->id,
                'colourway_id' => $colourway?->id,
                'size' => $size,
                'decoration' => $decoration,
                'quantity' => $quantity,
            ];
        }

        $this->persist($lines);

        return $key;
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $lines = $this->raw();

        if (! isset($lines[$key])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($key);

            return;
        }

        $product = Product::find($lines[$key]['product_id']);
        $lines[$key]['quantity'] = $product
            ? $this->normaliseQuantity($product, $quantity)
            : max(1, $quantity);

        $this->persist($lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->raw();
        unset($lines[$key]);
        $this->persist($lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->resolved = null;
    }

    /* ------------------------------------------------------------- Reading */

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(?Company $company = null): Collection
    {
        if ($this->resolved !== null && $this->pricedFor?->id === $company?->id) {
            return $this->resolved;
        }

        $raw = $this->raw();

        if ($raw === []) {
            return $this->resolved = collect();
        }

        $products = Product::with(['inventoryLevels.warehouse', 'category'])
            ->whereIn('id', array_column($raw, 'product_id'))
            ->get()
            ->keyBy('id');

        $colourways = Colourway::whereIn('id', array_filter(array_column($raw, 'colourway_id')))
            ->get()
            ->keyBy('id');

        $this->pricing->warmFor($company);
        $this->pricedFor = $company;

        $lines = collect();
        $pruned = $raw;

        foreach ($raw as $key => $row) {
            $product = $products->get($row['product_id']);

            // An item retired since the basket was filled simply drops out.
            if (! $product || ! $product->is_published) {
                unset($pruned[$key]);

                continue;
            }

            $lines->push(new CartLine(
                key: (string) $key,
                product: $product,
                colourway: $row['colourway_id'] ? $colourways->get($row['colourway_id']) : null,
                size: (string) $row['size'],
                decoration: (string) $row['decoration'],
                quantity: (int) $row['quantity'],
                quote: $this->pricing->quote($product, $company, (int) $row['quantity']),
            ));
        }

        if (count($pruned) !== count($raw)) {
            $this->persist($pruned);
        }

        return $this->resolved = $lines;
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /** Cheap enough for the header badge — no models loaded. */
    public function pieceCount(): int
    {
        return (int) array_sum(array_column($this->raw(), 'quantity'));
    }

    public function lineCount(): int
    {
        return count($this->raw());
    }

    /* -------------------------------------------------------------- Totals */

    public function totals(?Company $company = null): CartTotals
    {
        $lines = $this->lines($company);

        $merchandise = round($lines->sum(fn (CartLine $l) => $l->lineTotal()), 2);
        $pieces = (int) $lines->sum(fn (CartLine $l) => $l->quantity);

        return new CartTotals(
            merchandise: $merchandise,
            freight: $this->estimatedFreight($merchandise),
            pieces: $pieces,
            lineCount: $lines->count(),
        );
    }

    /**
     * An estimate only — freight is confirmed with the order acknowledgement,
     * which is why nothing here is ever written to an order as final.
     */
    public function estimatedFreight(float $merchandise): float
    {
        if ($merchandise <= 0) {
            return 0;
        }

        if ($merchandise >= (float) config('tbm.storefront.free_freight_over')) {
            return 0;
        }

        return round($merchandise * (float) config('tbm.storefront.freight_rate'), 2);
    }

    /* ---------------------------------------------------------- Validation */

    /**
     * Everything standing between the basket and a placed order.
     *
     * @return array<int, array{level:string, message:string}>
     */
    public function issues(?Company $company = null): array
    {
        $issues = [];

        foreach ($this->lines($company) as $line) {
            foreach ($line->warnings() as $warning) {
                $issues[] = $warning;
            }
        }

        return $issues;
    }

    public function hasBlockingIssues(?Company $company = null): bool
    {
        return collect($this->issues($company))->contains(fn (array $i) => $i['level'] === 'error');
    }

    /* ------------------------------------------------------------ Internals */

    private function raw(): array
    {
        return (array) $this->session->get(self::SESSION_KEY, []);
    }

    private function persist(array $lines): void
    {
        $this->session->put(self::SESSION_KEY, $lines);
        $this->resolved = null;
    }

    /**
     * Quantities are rounded up to the item's order step, because that is how
     * the goods are actually packed. Doing it here means every entry point —
     * item page, basket, reorder — behaves the same.
     */
    private function normaliseQuantity(Product $product, int $quantity): int
    {
        $step = max(1, (int) $product->order_step);
        $quantity = max((int) $product->moq, $quantity);

        return (int) (ceil($quantity / $step) * $step);
    }

    private function keyFor(Product $product, ?Colourway $colourway, string $size, string $decoration): string
    {
        return substr(md5(implode('|', [
            $product->id,
            $colourway?->id ?? 0,
            $size,
            $decoration,
        ])), 0, 12);
    }
}
