<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * The rule the business asked for, stated once: an order belongs to the
 * company, so every login on that company sees it — not only the person who
 * placed it. Acting on it is a separate question from seeing it.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() ? $user->can('orders.view') : true;
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return $user->can('orders.view');
        }

        return $user->company_id === $order->company_id;
    }

    public function create(User $user): bool
    {
        return $user->canPlaceOrders();
    }

    public function reorder(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $user->canPlaceOrders();
    }

    /**
     * A customer may withdraw their own account's order while it is still
     * awaiting confirmation; after that it is a phone call, because stock has
     * been allocated and the warehouse may have started picking.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return $user->can('orders.manage');
        }

        return $user->company_id === $order->company_id
            && $user->canPlaceOrders()
            && $order->isCancellable();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isStaff() && $user->can('orders.manage');
    }

    public function addInternalNote(User $user, Order $order): bool
    {
        return $user->isStaff() && $user->can('orders.manage');
    }

    /** Documents carry prices, so they follow the pricing gate. */
    public function downloadDocuments(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $user->canSeePricing();
    }

    /** The pick list carries mill references. Staff only, always. */
    public function viewPickList(User $user, Order $order): bool
    {
        return $user->isStaff();
    }
}
