<?php

namespace App\Services\Inventory;

use App\Models\InventoryLevel;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Every change to a stock figure goes through here.
 *
 * Two rules hold the whole thing together. First, a figure is never written
 * without a StockMovement recording what it was, what it became and why —
 * which is what lets the back office answer "who changed this and when".
 * Second, writes take a row lock inside a transaction, so two people
 * correcting the same line, or an import landing mid-checkout, cannot
 * interleave into a wrong number.
 */
class InventoryService
{
    /**
     * Set an absolute on-hand figure. This is what the morning import and a
     * cycle count both do.
     */
    public function setOnHand(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        string $reason,
        ?User $user = null,
        ?Model $source = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $reason, $user, $source, $note) {
            $level = $this->lockLevel($product, $warehouse);

            $before = (int) $level->on_hand;
            $after = max(0, $quantity);

            $level->forceFill([
                'on_hand' => $after,
                'synced_at' => now(),
            ])->save();

            return $this->record($product, $warehouse, $before, $after, $reason, $user, $source, $note);
        });
    }

    /** Move a figure by a delta — a receipt, a write-off, a return. */
    public function adjust(
        Product $product,
        Warehouse $warehouse,
        int $delta,
        string $reason,
        ?User $user = null,
        ?Model $source = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $warehouse, $delta, $reason, $user, $source, $note) {
            $level = $this->lockLevel($product, $warehouse);

            $before = (int) $level->on_hand;
            $after = max(0, $before + $delta);

            $level->forceFill(['on_hand' => $after])->save();

            return $this->record($product, $warehouse, $before, $after, $reason, $user, $source, $note);
        });
    }

    /**
     * Move stock between sites. Both legs are written in one transaction, so
     * the pair either lands together or not at all.
     *
     * @return array{0:StockMovement,1:StockMovement}
     */
    public function transfer(
        Product $product,
        Warehouse $from,
        Warehouse $to,
        int $quantity,
        ?User $user = null,
        ?string $note = null,
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('A transfer needs a positive quantity.');
        }

        if ($from->is($to)) {
            throw new \InvalidArgumentException('A transfer needs two different warehouses.');
        }

        return DB::transaction(function () use ($product, $from, $to, $quantity, $user, $note) {
            $source = $this->lockLevel($product, $from);

            if ($source->available() < $quantity) {
                throw new InsufficientStockException(sprintf(
                    '%s has %s available at %s; the transfer asks for %s.',
                    $product->sku,
                    number_format($source->available()),
                    $from->code,
                    number_format($quantity)
                ));
            }

            $note = $note ?: sprintf('Transfer %s → %s', $from->code, $to->code);

            return [
                $this->adjustLocked($product, $from, -$quantity, StockMovement::REASON_TRANSFER, $user, null, $note),
                $this->adjustLocked($product, $to, $quantity, StockMovement::REASON_TRANSFER, $user, null, $note),
            ];
        });
    }

    /* --------------------------------------------------------- Allocation */

    /**
     * Reserve stock against a confirmed order. Allocation is kept separate
     * from on_hand so the warehouse still sees the goods on the shelf while
     * the storefront stops offering them.
     */
    public function allocate(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_id || ! $order->warehouse_id) {
                    continue;
                }

                InventoryLevel::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->increment('allocated', $item->quantity);
            }
        });
    }

    /** Release a reservation — the order shipped, or it was cancelled. */
    public function release(Order $order, bool $consume = false): void
    {
        DB::transaction(function () use ($order, $consume) {
            foreach ($order->items as $item) {
                if (! $item->product_id || ! $order->warehouse_id) {
                    continue;
                }

                $level = InventoryLevel::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (! $level) {
                    continue;
                }

                $level->allocated = max(0, (int) $level->allocated - (int) $item->quantity);

                // On despatch the goods actually leave the building.
                if ($consume) {
                    $before = (int) $level->on_hand;
                    $level->on_hand = max(0, $before - (int) $item->quantity);
                    $level->save();

                    $this->record(
                        $item->product,
                        $order->warehouse,
                        $before,
                        (int) $level->on_hand,
                        StockMovement::REASON_ORDER,
                        null,
                        $order,
                        sprintf('Despatched on %s', $order->reference)
                    );

                    continue;
                }

                $level->save();
            }
        });
    }

    /* ------------------------------------------------------------ Reading */

    public function availableFor(Product $product, ?Warehouse $warehouse = null): int
    {
        $query = InventoryLevel::where('product_id', $product->id);

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        return (int) $query->get()->sum(fn (InventoryLevel $l) => $l->available());
    }

    public function canFulfil(Product $product, int $quantity, ?Warehouse $warehouse = null): bool
    {
        return $this->availableFor($product, $warehouse) >= $quantity;
    }

    /**
     * Weeks of cover, grossed up by the configured demand scale so the figure
     * reads against the whole customer base rather than the sample in the
     * database. Capped, because "infinite cover" is not a useful number.
     */
    public function weeksOfCover(Product $product, float $monthlyUsage): float
    {
        if ($monthlyUsage <= 0) {
            return 99;
        }

        $weekly = $monthlyUsage / 4.345;

        return min(99, round($product->totalStock() / max($weekly, 0.01), 1));
    }

    /* ---------------------------------------------------------- Internals */

    private function lockLevel(Product $product, Warehouse $warehouse): InventoryLevel
    {
        return InventoryLevel::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->firstOr(fn () => InventoryLevel::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'on_hand' => 0,
            ]));
    }

    /** Called only from inside an open transaction that already holds the lock. */
    private function adjustLocked(
        Product $product,
        Warehouse $warehouse,
        int $delta,
        string $reason,
        ?User $user,
        ?Model $source,
        ?string $note,
    ): StockMovement {
        $level = $this->lockLevel($product, $warehouse);
        $before = (int) $level->on_hand;
        $after = max(0, $before + $delta);

        $level->forceFill(['on_hand' => $after])->save();

        return $this->record($product, $warehouse, $before, $after, $reason, $user, $source, $note);
    }

    private function record(
        Product $product,
        Warehouse $warehouse,
        int $before,
        int $after,
        string $reason,
        ?User $user,
        ?Model $source,
        ?string $note,
    ): StockMovement {
        return StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user?->id ?? auth()->id(),
            'quantity_before' => $before,
            'quantity_after' => $after,
            'delta' => $after - $before,
            'reason' => $reason,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'note' => $note,
        ]);
    }
}
