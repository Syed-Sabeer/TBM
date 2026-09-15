<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The back-office door. Same credentials table, separate entrance: a customer
 * login is refused here rather than redirected, so nothing about the shape of
 * the back office is revealed to someone who does not belong in it.
 */
class AdminLoginController extends Controller
{
    public function show(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(staffOnly: true);
        $request->session()->regenerate();

        ActivityLog::record('Staff signed in', Auth::user()->email, Auth::user());

        return redirect()->intended(route('admin.dashboard'));
    }
}
