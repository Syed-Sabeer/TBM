<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Pricing\PricingService;
use App\Services\Rendering\BagRenderer;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         | These three are singletons for the same reason: each memoises
         | something within a request. PricingService caches an account's
         | negotiated rates, CartService caches the resolved basket, and
         | BagRenderer keeps a counter so two illustrations never share a
         | gradient id.
         */
        $this->app->singleton(PricingService::class);
        $this->app->singleton(BagRenderer::class);

        $this->app->singleton(CartService::class, fn ($app) => new CartService(
            $app->make(Session::class),
            $app->make(PricingService::class),
        ));
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('pagination.default');
        Paginator::defaultSimpleView('pagination.simple');

        // Guard against N+1 slipping into a listing page during development.
        Model::preventLazyLoading(! app()->isProduction());

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $this->registerBladeDirectives();
    }

    /**
     * Two directives, both about money.
     *
     * @price is the single gate that decides whether a figure may render. No
     * view formats a price by hand; they ask this, and a signed-out visitor or
     * an unapproved account gets the "sign in to see pricing" treatment
     * everywhere at once.
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('usd', fn ($expression) => "<?php echo \App\Support\Money::format($expression); ?>");

        Blade::if('pricing', fn () => auth()->check() && auth()->user()->canSeePricing());

        Blade::if('staff', fn () => auth()->check() && auth()->user()->isStaff());

        Blade::if('customer', fn () => auth()->check() && auth()->user()->isCustomer());
    }
}
