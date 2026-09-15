<?php

namespace App\Providers;

use App\Http\Composers\NavigationComposer;
use App\Http\Composers\StorefrontComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Everything the shared layouts need is bound here rather than repeated in
 * each controller. A new page gets a working header and footer for free, and
 * no controller can forget to pass the basket count.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(
            ['layouts.storefront', 'partials.header', 'partials.footer', 'partials.mega-menu'],
            StorefrontComposer::class
        );

        View::composer(
            ['layouts.account', 'layouts.admin', 'partials.account-nav', 'partials.admin-sidebar'],
            NavigationComposer::class
        );

        View::share('poweredBy', config('tbm.powered_by'));
        View::share('tbmCompany', config('tbm.company'));
    }
}
