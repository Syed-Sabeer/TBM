<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The back office is staff only. A customer login that finds its way to an
 * admin URL is sent to its own dashboard rather than shown a 403 — there is
 * nothing for them to fix, and a forbidden page only invites a second attempt.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->isStaff()) {
            return redirect()->route('account.dashboard');
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'That account has been deactivated.']);
        }

        return $next($request);
    }
}
