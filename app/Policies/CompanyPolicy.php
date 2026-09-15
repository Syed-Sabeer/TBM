<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->isStaff()) {
            return $user->can('companies.view');
        }

        return $user->company_id === $company->id;
    }

    /** A customer admin keeps their own company details current. */
    public function update(User $user, Company $company): bool
    {
        if ($user->isStaff()) {
            return $user->can('companies.manage');
        }

        return $user->company_id === $company->id
            && $user->hasRole('customer-admin');
    }

    /** Approving an account releases pricing to it. Staff only. */
    public function approve(User $user, Company $company): bool
    {
        return $user->isStaff() && $user->can('companies.approve');
    }

    /** Which tier an account sits on, and any negotiated item rates. */
    public function setRates(User $user, Company $company): bool
    {
        return $user->isStaff() && $user->can('pricing.manage');
    }

    public function setCredit(User $user, Company $company): bool
    {
        return $user->isStaff() && $user->can('companies.manage');
    }

    public function inviteUsers(User $user, Company $company): bool
    {
        if ($user->isStaff()) {
            return $user->can('companies.manage');
        }

        return $user->company_id === $company->id && $user->hasRole('customer-admin');
    }
}
