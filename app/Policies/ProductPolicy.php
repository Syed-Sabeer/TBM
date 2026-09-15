<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** The catalogue is public; only the price behind it is not. */
    public function view(?User $user, Product $product): bool
    {
        return $product->is_published || (bool) $user?->can('catalogue.manage');
    }

    public function seePrice(?User $user, Product $product): bool
    {
        return (bool) $user?->canSeePricing();
    }

    /** The mill reference. Never a customer, on any screen. */
    public function seeMillReference(?User $user, Product $product): bool
    {
        return (bool) $user?->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->can('catalogue.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('catalogue.manage');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('catalogue.manage');
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $user->can('inventory.manage');
    }
}
