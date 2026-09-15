<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff()
            ? $user->can('users.manage')
            : $user->hasRole('customer-admin');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->isStaff()) {
            return $user->can('users.manage');
        }

        return $user->company_id === $target->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff()
            ? $user->can('users.manage')
            : $user->hasRole('customer-admin');
    }

    public function update(User $user, User $target): bool
    {
        // Everyone keeps their own profile up to date.
        if ($user->is($target)) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->can('users.manage');
        }

        return $user->company_id === $target->company_id
            && $user->hasRole('customer-admin');
    }

    /** Nobody deactivates themselves and locks the account out. */
    public function deactivate(User $user, User $target): bool
    {
        return ! $user->is($target) && $this->update($user, $target);
    }

    public function changeRole(User $user, User $target): bool
    {
        return ! $user->is($target) && $this->update($user, $target);
    }
}
