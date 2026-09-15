<?php

use App\Http\Middleware\EnsureAccountCanTrade;
use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\RecordLastSeen;
use App\Services\Imports\ImportException;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Orders\OrderException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

/*
|--------------------------------------------------------------------------
| The application
|--------------------------------------------------------------------------
|
| Laravel 11 replaced the HTTP kernel, the console kernel and the exception
| handler with this one file. Everything those three classes used to hold is
| here: the route files, the middleware stack, the aliases, and how the
| domain's own exceptions turn into a response.
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        /*
         | The back office is a second route file rather than a prefix group
         | inside web.php: it keeps every admin URL and route name in one
         | place, and `admin.` is applied once here instead of on each group.
         */
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )

    ->withMiddleware(function (Middleware $middleware): void {
        /*
         | Keeps a rough "last seen" on each login so the back office can tell
         | a live contact from a dormant one. Throttled to once an hour inside
         | the middleware itself.
         */
        $middleware->web(append: [
            RecordLastSeen::class,
        ]);

        $middleware->alias([
            // TBM
            'staff' => EnsureUserIsStaff::class,
            'customer' => EnsureUserIsCustomer::class,
            'trading' => EnsureAccountCanTrade::class,

            // Spatie
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        /*
         | Nothing else to configure here. Laravel 11 folded the old skeleton's
         | middleware stubs into the framework, and its TrimStrings already
         | leaves `password`, `password_confirmation` and `current_password`
         | alone — leading and trailing spaces are part of a password.
         */
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        /*
         | The domain exceptions all mean the same thing to a user: the thing
         | you asked for cannot happen, and here is why in plain words. They
         | come back as a message on the page they were already on rather than
         | a stack trace or a bare 500.
         */
        $exceptions->render(function (
            OrderException|ImportException|InsufficientStockException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['tbm' => $e->getMessage()]);
        });
    })

    ->create();
