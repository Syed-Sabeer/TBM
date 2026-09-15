<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isCustomer()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'That login has been deactivated. Speak to your account admin.']);
        }

        return $next($request);
    }
}
