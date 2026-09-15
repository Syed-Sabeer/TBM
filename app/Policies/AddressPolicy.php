<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isCustomer();
    }

    public function view(User $user, Address $address): bool
    {
        return $user->isStaff() || $user->company_id === $address->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff()
            || ($user->isCustomer() && $user->hasAnyRole(['customer-admin', 'customer-buyer']));
    }

    public function update(User $user, Address $address): bool
    {
        if ($user->isStaff()) {
            return $user->can('companies.manage');
        }

        return $user->company_id === $address->company_id
            && $user->hasAnyRole(['customer-admin', 'customer-buyer']);
    }

    public function delete(User $user, Address $address): bool
    {
        return $this->update($user, $address);
    }
}
