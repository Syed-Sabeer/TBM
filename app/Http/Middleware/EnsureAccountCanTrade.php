<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the routes that move money or stock — checkout, reorder, order
 * placement. An account still awaiting approval, on hold, or with a lapsed
 * resale certificate keeps its portal and its history; it just cannot buy.
 *
 * The customer is sent somewhere that explains why, because "you are on hold"
 * is actionable and a 403 is not.
 */
class EnsureAccountCanTrade
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if (! $company) {
            return redirect()->route('login');
        }

        if (! $company->canTrade()) {
            return redirect()
                ->route('account.dashboard')
                ->with('status', $this->explain($company));
        }

        return $next($request);
    }

    private function explain($company): string
    {
        if (! $company->status->canTrade()) {
            return sprintf(
                'Your account is %s, so ordering is paused. Your account manager can pick this up.',
                strtolower($company->status->label())
            );
        }

        return 'We need a current resale certificate on file before this account can order again.';
    }
}
