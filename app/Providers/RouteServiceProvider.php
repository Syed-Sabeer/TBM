<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /** Where a customer lands after signing in. */
    public const HOME = '/account';

    /** Where a staff member lands after signing in. */
    public const ADMIN_HOME = '/admin';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        });
    }

    public static function homeFor(?User $user): string
    {
        return $user?->isStaff() ? self::ADMIN_HOME : self::HOME;
    }

    private function configureRateLimiting(): void
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

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
