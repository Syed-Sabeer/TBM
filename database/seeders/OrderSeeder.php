<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Pricing\PricingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Twenty-six months of order history.
 *
 * Two things make this worth doing properly rather than with random numbers.
 *
 * First, every line is priced through PricingService against the account that
 * placed it — so the seeded history is internally consistent with the live
 * rate card, and the reports built on top of it tell the truth.
 *
 * Second, it runs 26 months rather than 12 with a gentle growth curve, so the
 * year-on-year figures on every dashboard have a real prior period to compare
 * against instead of a fabricated one.
 *
 * The generator is seeded, so the same history comes out every time and a
 * screenshot taken today still matches next week.
 */
class OrderSeeder extends Seeder
{
    private const MONTHS = 26;

    private int $seed = 20260914;

    /** Reference sequence per year, so no two orders ever collide. */
    private array $sequence = [];

    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function run(): void
    {
        $companies = Company::with(['tier', 'users', 'addresses', 'priceOverrides'])
            ->whereHas('users')
            ->get()
            ->filter(fn (Company $c) => $c->status->canTrade());

        if ($companies->isEmpty()) {
            $this->command?->warn('  No trading accounts — skipping orders.');

            return;
        }

        $products = Product::published()->with('colourways')->get();
        $warehouses = Warehouse::onStorefront()->get();

        $made = 0;

        for ($back = self::MONTHS - 1; $back >= 0; $back--) {
            $month = now()->startOfMonth()->subMonths($back);

            // Rising baseline, so "vs last year" is a real number rather than
            // noise. Older months sit lower; the last few sit above 1.0.
            $growth = 0.78 + ((self::MONTHS - 1 - $back) * 0.018);

            foreach ($companies as $company) {
                $orders = $this->ordersThisMonth($company, $growth);

                for ($i = 0; $i < $orders; $i++) {
                    $this->makeOrder($company, $products, $warehouses, $month, $back);
                    $made++;
                }
            }
        }

        $this->command?->info(sprintf('  %d orders across %d months', $made, self::MONTHS));
    }

    private function ordersThisMonth(Company $company, float $growth): int
    {
        // Bigger tiers order more often. Tier A is the container trade.
        $base = match ($company->tier?->code) {
            'A' => 3.2,
            'B' => 2.0,
            default => 1.1,
        };

        return max(0, (int) round($base * $growth * $this->rand(0.6, 1.5)));
    }

    private function makeOrder(
        Company $company,
        Collection $products,
        Collection $warehouses,
        Carbon $month,
        int $monthsBack,
    ): void {
        $placedAt = $month->copy()
            ->addDays($this->randInt(0, $month->daysInMonth - 1))
            ->addHours($this->randInt(8, 17));

        if ($placedAt->isFuture()) {
            $placedAt = now()->subHours($this->randInt(1, 48));
        }

        // Only people who may actually place orders appear as the buyer.
        $buyers = $company->users->filter(fn ($u) => $u->hasAnyRole(['customer-admin', 'customer-buyer']));
        $buyer = $buyers->isNotEmpty() ? $buyers->get($this->randInt(0, $buyers->count() - 1)) : $company->users->first();

        $warehouse = $warehouses->get($this->randInt(0, $warehouses->count() - 1));
        $address = $company->addresses->first();

        $status = $this->statusFor($monthsBack);

        $order = Order::create([
            'reference' => $this->reference($placedAt),
            'company_id' => $company->id,
            'placed_by_id' => $buyer?->id,
            'warehouse_id' => $warehouse->id,
            'address_id' => $address?->id,
            'status' => $status,
            'customer_po' => $this->chance(0.65) ? 'PO-'.$this->randInt(10000, 99999) : null,
            'job_reference' => $this->chance(0.35) ? $this->jobReference() : null,
            'shipping_service' => $this->pick(['Ground — 3 to 5 business days', 'Two-day air', 'Customer collect']),
            'payment_terms' => $company->payment_terms,
            'ship_to' => $address?->snapshot(),
            'placed_at' => $placedAt,
            'confirmed_at' => $status->step() >= 2 ? $placedAt->copy()->addHours(3) : null,
            'shipped_at' => $status->step() >= 4 ? $placedAt->copy()->addDays(4) : null,
            'delivered_at' => $status->step() >= 5 ? $placedAt->copy()->addDays(7) : null,
        ]);

        $this->addLines($order, $company, $products);
    }

    private function addLines(Order $order, Company $company, Collection $products): void
    {
        $lineCount = $this->randInt(1, 4);
        $merchandise = 0.0;
        $pieces = 0;

        foreach ($this->sample($products, $lineCount) as $product) {
            $quantity = $this->quantityFor($product, $company);
            $colourway = $product->colourways->isNotEmpty()
                ? $product->colourways->get($this->randInt(0, $product->colourways->count() - 1))
                : null;

            // The same engine the storefront uses, so history and live pricing
            // can never disagree.
            $quote = $this->pricing->quote($product, $company, $quantity);

            $order->items()->create([
                'product_id' => $product->id,
                'colourway_id' => $colourway?->id,
                'sku' => $product->sku,
                'parent_sku' => $product->parent_sku,
                'name' => $product->name,
                'colour_name' => $colourway?->name ?? 'As shown',
                'size' => $product->defaultSize(),
                'decoration' => $this->decoration(),
                'quantity' => $quantity,
                'unit_price' => $quote->unitPrice,
                'unit_cost' => $product->effectiveCost(),
                'line_total' => $quote->lineTotal(),
            ]);

            $merchandise += $quote->lineTotal();
            $pieces += $quantity;
        }

        $freight = $merchandise >= config('tbm.storefront.free_freight_over')
            ? 0
            : round($merchandise * config('tbm.storefront.freight_rate'), 2);

        $order->forceFill([
            'merchandise_total' => round($merchandise, 2),
            'freight_total' => $freight,
            'grand_total' => round($merchandise + $freight, 2),
            'total_pieces' => $pieces,
        ])->save();
    }

    /** Distinct items, picked from the deterministic generator. */
    private function sample(Collection $products, int $count): Collection
    {
        $picked = collect();

        while ($picked->count() < min($count, $products->count())) {
            $candidate = $products->get($this->randInt(0, $products->count() - 1));

            if (! $picked->contains('id', $candidate->id)) {
                $picked->push($candidate);
            }
        }

        return $picked;
    }

    private function quantityFor(Product $product, Company $company): int
    {
        $breaks = $this->pricing->breakQuantities();

        // Tier A buys deeper, so weight it towards the larger breaks.
        $ceiling = match ($company->tier?->code) {
            'A' => count($breaks) - 1,
            'B' => count($breaks) - 2,
            default => count($breaks) - 3,
        };

        $target = $breaks[$this->randInt(0, max(0, $ceiling))];
        $step = max(1, $product->order_step);

        return (int) max($product->moq, ceil($target * $this->rand(1.0, 1.6) / $step) * $step);
    }

    /**
     * Old orders are delivered; recent ones are still moving. A small slice is
     * cancelled, because the reports need to prove they exclude those.
     */
    private function statusFor(int $monthsBack): OrderStatus
    {
        if ($this->chance(0.03)) {
            return OrderStatus::Cancelled;
        }

        if ($monthsBack > 1) {
            return OrderStatus::Delivered;
        }

        if ($monthsBack === 1) {
            return $this->pick([OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Shipped]);
        }

        return $this->pick([
            OrderStatus::PendingConfirmation,
            OrderStatus::Confirmed,
            OrderStatus::InProduction,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ]);
    }

    private function decoration(): string
    {
        return $this->chance(0.55)
            ? 'Blank (undecorated)'
            : $this->pick(['Screen print', 'DTF transfer', 'Embroidery', 'Sublimation']);
    }

    private function jobReference(): string
    {
        return $this->pick(['Summer festival', 'Q3 retail rollout', 'Conference giveaway', 'Store opening',
            'Member welcome pack', 'Campus orientation', 'Farmers market', 'Holiday gifting']);
    }

    private function reference(Carbon $placedAt): string
    {
        $year = $placedAt->format('Y');
        $this->sequence[$year] = ($this->sequence[$year] ?? 1000) + 1;

        return sprintf('TBM-%s-%04d', $year, $this->sequence[$year]);
    }

    /* --------------------------------------------- Deterministic randomness */

    /** A tiny LCG, so the same history is produced on every run. */
    private function next(): float
    {
        $this->seed = ($this->seed * 1103515245 + 12345) % 2147483648;

        return $this->seed / 2147483648;
    }

    private function rand(float $min, float $max): float
    {
        return $min + ($max - $min) * $this->next();
    }

    private function randInt(int $min, int $max): int
    {
        return (int) floor($this->rand($min, $max + 0.999));
    }

    private function chance(float $probability): bool
    {
        return $this->next() < $probability;
    }

    private function pick(array $options)
    {
        return $options[$this->randInt(0, count($options) - 1)];
    }
}
