<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.settings', [
            'user' => $request->user(),
            'company' => $request->user()->company,
        ]);
    }

    /**
     * Profile fields for anyone; company fields only for an account admin,
     * which the policy decides rather than this method.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        if ($request->filled('company_name') && $request->user()->can('update', $user->company)) {
            $company = $user->company;

            $company->update($request->validate([
                'company_name' => ['required', 'string', 'max:160'],
                'website' => ['nullable', 'string', 'max:160'],
                'billing_street' => ['required', 'string', 'max:160'],
                'billing_city' => ['required', 'string', 'max:80'],
                'billing_state' => ['required', 'string', 'max:32'],
                'billing_postcode' => ['required', 'string', 'max:16'],
            ]) + ['name' => $request->input('company_name')]);

            ActivityLog::record('Company details updated', $company->name, $company);
        }

        return back()->with('status', 'Saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()->min(10)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        ActivityLog::record('Password changed', $request->user()->email, $request->user());

        return back()->with('status', 'Password changed.');
    }
}
