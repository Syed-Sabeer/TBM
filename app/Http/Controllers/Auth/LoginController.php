<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The storefront login. Staff have their own door at /admin/login; a staff
 * member who signs in here is simply redirected onward to the back office
 * rather than turned away, because they used valid credentials.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        ActivityLog::record('Signed in', $user->email, $user);

        return redirect()->intended(RouteServiceProvider::homeFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasStaff = $request->user()?->isStaff();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasStaff ? 'admin.login' : 'home');
    }
}
