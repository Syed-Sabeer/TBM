<?php

namespace App\Http\Controllers\Account;

use App\Enums\CompanyUserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "We want one company, different logins with different users in the company,
 * but they all should have the same order history."
 *
 * So: an account admin adds colleagues here. The new login inherits the
 * company's rate card automatically — there is nothing on this page that
 * touches price, because price belongs to the account, not the person.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('account.users', [
            'users' => $company->users()->with('roles')->orderBy('name')->get(),
            'roles' => CompanyUserRole::cases(),
            'canManage' => $request->user()->can('inviteUsers', $company),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $this->authorize('inviteUsers', $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'job_title' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(array_column(CompanyUserRole::cases(), 'value'))],
            'password' => ['required', 'confirmed', Password::defaults()->min(10)],
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        ActivityLog::record(
            'Account user added',
            sprintf('%s added %s to %s', $request->user()->name, $user->email, $company->name),
            $user
        );

        return back()->with('status', sprintf(
            '%s can now sign in. They see the same pricing and the same order history as everyone else on the account.',
            $user->firstName()
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'role' => ['nullable', Rule::in(array_column(CompanyUserRole::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['role']) && $request->user()->can('changeRole', $user)) {
            $user->syncRoles([$data['role']]);
        }

        if ($request->has('is_active') && $request->user()->can('deactivate', $user)) {
            $user->update(['is_active' => $request->boolean('is_active')]);
        }

        ActivityLog::record('Account user updated', $user->email, $user);

        return back()->with('status', sprintf('%s updated.', $user->firstName()));
    }

    /**
     * A login is deactivated rather than deleted: the orders they placed stay
     * attributed, so the account's history does not develop holes.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->update(['is_active' => false]);

        ActivityLog::record('Account user deactivated', $user->email, $user);

        return back()->with('status', sprintf(
            '%s can no longer sign in. Their past orders stay on the account history.',
            $user->firstName()
        ));
    }
}
