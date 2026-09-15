<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Staff and customers have separate front doors, so an expired session in
     * the back office returns to the back-office login rather than dumping a
     * colleague on the storefront.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login');
    }
}
