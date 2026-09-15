<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Staff logins. A role here is a job title, and the permissions behind it are
 * defined once in RoleSeeder rather than ticked per person — which is what
 * stops the back office drifting into a mess of individual exceptions.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::staff()->with('roles')->orderBy('name')->get(),
            'roles' => Role::whereIn('name', array_column(StaffRole::cases(), 'value'))->withCount('permissions')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true]), 'roles' => StaffRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'job_title' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(array_column(StaffRole::cases(), 'value'))],
            'password' => ['required', Password::defaults()->min(12)],
        ]);

        $user = User::create([
            'company_id' => null,      // staff: no company
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        ActivityLog::record('Staff login created', sprintf('%s as %s', $user->email, $data['role']), $user);

        return redirect()->route('admin.users.index')->with('status', $user->name.' can now sign in.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->isStaff(), 404);

        return view('admin.users.form', ['user' => $user, 'roles' => StaffRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(array_column(StaffRole::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', Password::defaults()->min(12)],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->user()->is($user) ? true : $request->boolean('is_active'),
        ] + array_filter(['password' => $data['password'] ?? null]));

        if (! $request->user()->is($user)) {
            $user->syncRoles([$data['role']]);
        }

        ActivityLog::record('Staff login updated', $user->email, $user);

        return back()->with('status', $user->name.' saved.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'You cannot deactivate your own login.');

        $user->update(['is_active' => false]);

        ActivityLog::record('Staff login deactivated', $user->email, $user);

        return back()->with('status', $user->name.' can no longer sign in.');
    }
}
