<?php

namespace App\Http\Composers;

use App\Enums\AccountStatus;
use App\Enums\ImportStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\StockImport;
use Illuminate\View\View;

/**
 * The counters beside the navigation items in both back-office and portal
 * sidebars. They are what makes a sidebar useful rather than decorative — a
 * "3" next to Orders is the reason someone clicks it.
 */
class NavigationComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $view->with('navBadges', $user->isStaff()
            ? $this->staffBadges()
            : $this->customerBadges($user->company));
    }

    private function staffBadges(): array
    {
        return cache()->remember('nav.badges.staff', now()->addMinutes(2), fn () => [
            'orders' => Order::where('status', OrderStatus::PendingConfirmation->value)->count(),
            'companies' => Company::where('status', AccountStatus::PendingApproval->value)->count(),
            'imports' => StockImport::where('status', ImportStatus::Previewed->value)->count(),
        ]);
    }

    private function customerBadges(?Company $company): array
    {
        if (! $company) {
            return [];
        }

        return [
            'orders' => Order::forCompany($company)->open()->count(),
        ];
    }
}
