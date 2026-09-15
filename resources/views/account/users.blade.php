@extends('layouts.account')

@section('title', 'Company users')
@section('heading', 'Company users')
@section('subheading', 'Everyone here shares the same pricing, the same addresses and the same order history.')

@section('panel')

<section class="panel">
    <div class="panel-head">
        <h3>{{ $users->count() }} {{ Str::plural('login', $users->count()) }}</h3>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Last seen</th><th>Status</th>@if ($canManage)<th></th>@endif</tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="user-row">
                                <span class="avatar">{{ $user->initials() }}</span>
                                <span>
                                    <b>{{ $user->name }}</b>
                                    @if ($user->job_title)<span class="muted">{{ $user->job_title }}</span>@endif
                                </span>
                            </div>
                        </td>
                        <td class="muted">{{ $user->email }}</td>
                        <td>
                            @if ($canManage && ! $user->is(auth()->user()))
                                <form method="POST" action="{{ route('account.users.update', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select class="select select-sm" name="role" onchange="this.form.submit()">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($user->hasRole($role->value))>
                                                {{ $role->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="badge">{{ $user->companyRole()?->label() ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="muted nw">{{ $user->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-ok' : 'badge-bad' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        @if ($canManage)
                            <td class="num">
                                @can('deactivate', $user)
                                    <form method="POST" action="{{ route('account.users.destroy', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost btn-sm" type="submit">Deactivate</button>
                                    </form>
                                @endcan
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- What each role can do, spelled out — this is the question support gets. --}}
<section class="panel">
    <div class="panel-head"><h3>What the roles mean</h3></div>
    <dl class="role-list">
        @foreach ($roles as $role)
            <div>
                <dt>{{ $role->label() }}</dt>
                <dd>{{ $role->description() }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="hint">None of these change what you are charged. Price comes from the account, not the person.</p>
</section>

@if ($canManage)
    <section class="panel">
        <div class="panel-head"><h3>Add a colleague</h3></div>

        <form method="POST" action="{{ route('account.users.store') }}">
            @csrf

            <div class="field-grid">
                <div class="field">
                    <label class="label" for="name">Full name</label>
                    <input class="input" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label class="label" for="email">Work email</label>
                    <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label class="label" for="job_title">Job title <small>optional</small></label>
                    <input class="input" id="job_title" name="job_title" value="{{ old('job_title') }}">
                </div>
                <div class="field">
                    <label class="label" for="phone">Phone <small>optional</small></label>
                    <input class="input" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="field">
                    <label class="label" for="role">Role</label>
                    <select class="select" id="role" name="role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="password">Temporary password</label>
                    <input class="input" id="password" type="password" name="password" required autocomplete="new-password">
                    <p class="hint">At least 10 characters. Ask them to change it after their first sign-in.</p>
                </div>
                <div class="field">
                    <label class="label" for="password_confirmation">Confirm password</label>
                    <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <button class="btn btn-primary" type="submit">Add this login <x-icon name="arrow"/></button>
        </form>
    </section>
@endif

@endsection
