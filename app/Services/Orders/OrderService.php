<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

/**
 * Turns a basket into an order, and moves an order through its states.
 *
 * The moment of placement is where live figures become history: prices, the
 * ship-to address and the item descriptions are all copied onto the order.
 * From then on nothing upstream — a rate card, an address edit, a product
 * rename — can change what the customer agreed to.
 */
class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InventoryService $inventory,
    ) {
    }

    /**
     * @param  array{customer_po?:string, job_reference?:string, shipping_service?:string, in_hands_on?:string, customer_notes?:string}  $details
     */
    public function placeFromCart(
        User $user,
        Address $address,
        Warehouse $warehouse,
        array $details = [],
    ): Order {
        $company = $user->company;

        if (! $company || ! $company->canTrade()) {
            throw new OrderException('This account is not cleared to place orders.');
        }

        $lines = $this->cart->lines($company);

        if ($lines->isEmpty()) {
            throw new OrderException('There is nothing in the basket.');
        }

        if ($address->company_id !== $company->id) {
            throw new OrderException('That address does not belong to this account.');
        }

        return DB::transaction(function () use ($user, $company, $address, $warehouse, $details, $lines) {
            $totals = $this->cart->totals($company);

            $order = Order::create([
                'reference' => Order::nextReference(),
                'company_id' => $company->id,
                'placed_by_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'address_id' => $address->id,
                'status' => OrderStatus::PendingConfirmation,
                'customer_po' => $details['customer_po'] ?? null,
                'job_reference' => $details['job_reference'] ?? null,
                'shipping_service' => $details['shipping_service'] ?? null,
                'payment_terms' => $company->payment_terms,
                'in_hands_on' => $details['in_hands_on'] ?? null,
                'merchandise_total' => $totals->merchandise,
                'decoration_total' => 0,   // quoted by a rep once artwork lands
                'freight_total' => $totals->freight,
                'tax_total' => 0,          // resale certificate on file
                'grand_total' => $totals->estimatedTotal(),
                'total_pieces' => $totals->pieces,
                'ship_to' => $address->snapshot(),
                'customer_notes' => $details['customer_notes'] ?? null,
                'placed_at' => now(),
            ]);

            $lines->each(fn (CartLine $line) => $order->items()->create($line->toOrderItemAttributes()));

            $this->chargeCredit($company, (float) $order->grand_total);

            $this->cart->clear();

            ActivityLog::record(
                'Order placed',
                sprintf('%s placed %s for %s', $user->name, $order->reference, $company->name),
                $order
            );

            return $order->load('items');
        });
    }

    /* ---------------------------------------------------------- Transitions */

    public function confirm(Order $order, ?User $by = null): Order
    {
        $this->assertStatus($order, [OrderStatus::PendingConfirmation]);

        $order->forceFill([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ])->save();

        // Only now is the stock actually spoken for.
        $this->inventory->allocate($order);

        ActivityLog::record('Order confirmed', $order->reference, $order);

        return $order;
    }

    public function markInProduction(Order $order): Order
    {
        $this->assertStatus($order, [OrderStatus::Confirmed]);

        $order->forceFill(['status' => OrderStatus::InProduction])->save();
        ActivityLog::record('Order moved to production', $order->reference, $order);

        return $order;
    }

    public function markShipped(Order $order, ?string $service = null): Order
    {
        $this->assertStatus($order, [OrderStatus::Confirmed, OrderStatus::InProduction]);

        $order->forceFill([
            'status' => OrderStatus::Shipped,
            'shipping_service' => $service ?: $order->shipping_service,
            'shipped_at' => now(),
        ])->save();

        // Goods leave the building: the reservation becomes a real decrement.
        $this->inventory->release($order, consume: true);

        ActivityLog::record('Order shipped', $order->reference, $order);

        return $order;
    }

    public function markDelivered(Order $order): Order
    {
        $this->assertStatus($order, [OrderStatus::Shipped]);

        $order->forceFill([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ])->save();

        ActivityLog::record('Order delivered', $order->reference, $order);

        return $order;
    }

    public function cancel(Order $order, string $reason, ?User $by = null): Order
    {
        if (in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw new OrderException('An order that has shipped cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $reason, $by) {
            if ($order->status !== OrderStatus::PendingConfirmation) {
                $this->inventory->release($order);
            }

            $order->forceFill(['status' => OrderStatus::Cancelled])->save();

            $order->notes()->create([
                'user_id' => $by?->id ?? auth()->id(),
                'body' => $reason,
                'is_internal' => false,
            ]);

            $this->refundCredit($order->company, (float) $order->grand_total);
        });

        ActivityLog::record('Order cancelled', sprintf('%s — %s', $order->reference, $reason), $order);

        return $order;
    }

    /* ------------------------------------------------------------- Reorder */

    /** Drop every line of a past order back into the basket at today's prices. */
    public function reorder(Order $order): int
    {
        $added = 0;

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->is_published) {
                continue;
            }

            $this->cart->add(
                $item->product,
                $item->colourway,
                $item->size,
                $item->decoration,
                $item->quantity
            );

            $added++;
        }

        return $added;
    }

    /* ----------------------------------------------------------- Internals */

    private function chargeCredit(Company $company, float $amount): void
    {
        $company->forceFill([
            'credit_used' => (float) $company->credit_used + $amount,
        ])->save();
    }

    private function refundCredit(Company $company, float $amount): void
    {
        $company->forceFill([
            'credit_used' => max(0, (float) $company->credit_used - $amount),
        ])->save();
    }

    /**
     * @param  array<int, OrderStatus>  $allowed
     */
    private function assertStatus(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new OrderException(sprintf(
                '%s is %s, which does not allow that step.',
                $order->reference,
                strtolower($order->status->label())
            ));
        }
    }
}
