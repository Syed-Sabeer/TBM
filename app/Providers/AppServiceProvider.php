<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Pricing\PricingService;
use App\Services\Rendering\BagRenderer;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->registerAuthRedirects();
        $this->registerRateLimiters();
        $this->registerBladeDirectives();
    }

    /**
     * Staff and customers have separate front doors.
     *
     * Laravel 11 dropped the skeleton's own Authenticate and
     * RedirectIfAuthenticated classes in favour of the framework's, so the two
     * redirects that used to live in those files are configured here instead.
     */
    private function registerAuthRedirects(): void
    {
        // An expired session in the back office returns to the back-office
        // login rather than dumping a colleague on the storefront.
        Authenticate::redirectUsing(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));

        // Someone already signed in who lands on a login page goes wherever
        // they actually belong.
        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => $request->user()?->homeUrl() ?? '/');
    }

    private function registerRateLimiters(): void
    {
        /*
         | Login attempts are limited per email AND per IP together, so one
         | attacker hammering many accounts is throttled just as a single
         | account under attack is.
         */
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by($request->input('email').'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
    }

    /**
     * Two directives, both about money.
     *
     * @pricing is the single gate that decides whether a figure may render. No
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
