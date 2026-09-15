<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockImport;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockImportPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Authorisation.
 *
 * Laravel discovers `App\Policies\XPolicy` for `App\Models\X` on its own, and
 * every policy here follows that convention — but the mapping is written out
 * anyway. A list of which models are governed at all is worth having in one
 * readable place, and it means adding a model without a policy is a visible
 * omission rather than a silent one.
 *
 * Laravel 11 dropped the framework's AuthServiceProvider base class, so this
 * is a plain service provider registered in bootstrap/providers.php.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $policies = [
        Address::class => AddressPolicy::class,
        Company::class => CompanyPolicy::class,
        Order::class => OrderPolicy::class,
        Product::class => ProductPolicy::class,
        StockImport::class => StockImportPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        /*
         | The owner can do anything. Returning null rather than false for
         | everyone else lets the policies have their say — a before callback
         | that returns false would short-circuit them.
         */
        Gate::before(fn (User $user) => $user->hasRole('owner') ? true : null);

        /*
         | The pricing gate.
         |
         | Every price in the application — a listing card, an item page, a
         | basket line, a PDF — is behind this one check. A staff member needs
         | the permission; a customer needs their account to be approved and
         | trading. One rule, so there is no page where a figure leaks because
         | someone forgot a condition in a view.
         */
        Gate::define('see-pricing', fn (User $user) => $user->canSeePricing());

        Gate::define('place-orders', fn (User $user) => $user->canPlaceOrders());

        Gate::define('view-margin', fn (User $user) => $user->isStaff() && $user->can('reports.margin'));

        /*
         | The mill reference. Visible to staff who raise purchase orders and
         | pick stock; never to a customer, on any screen, at any time.
         */
        Gate::define('see-mill-reference', fn (User $user) => $user->isStaff());
    }
}
