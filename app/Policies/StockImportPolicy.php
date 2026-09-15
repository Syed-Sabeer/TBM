<?php

namespace App\Policies;

use App\Models\StockImport;
use App\Models\User;

class StockImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can('imports.run');
    }

    public function view(User $user, StockImport $import): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->can('imports.run');
    }

    /**
     * Applying is separated from staging on purpose: an apply rewrites live
     * stock, so it is the step that carries the permission.
     */
    public function apply(User $user, StockImport $import): bool
    {
        return $user->isStaff()
            && $user->can('imports.apply')
            && $import->canBeApplied();
    }

    public function rollBack(User $user, StockImport $import): bool
    {
        return $user->isStaff()
            && $user->can('imports.apply')
            && $import->canBeRolledBack();
    }
}
