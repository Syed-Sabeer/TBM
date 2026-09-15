@extends('layouts.admin')

@section('title', $user->exists ? $user->name : 'New staff login')
@section('heading', $user->exists ? 'Edit '.$user->name : 'Add a staff login')

@section('content')

<section class="panel">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="field-grid">
            <div class="field">
                <label class="label" for="name">Full name</label>
                <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="job_title">Job title</label>
                <input class="input" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}">
            </div>
            <div class="field">
                <label class="label" for="phone">Phone</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="field">
                <label class="label" for="role">Role</label>
                <select class="select" id="role" name="role" required @if ($user->is(auth()->user())) disabled @endif>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($user->hasRole($role->value))>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @if ($user->is(auth()->user()))
                    <p class="hint">You cannot change your own role.</p>
                @endif
            </div>
            <div class="field">
                <label class="label" for="password">{{ $user->exists ? 'New password' : 'Password' }} @if ($user->exists)<small>leave blank to keep</small>@endif</label>
                <input class="input" id="password" type="password" name="password" autocomplete="new-password" @required(! $user->exists)>
                <p class="hint">At least 12 characters for a staff login.</p>
                @error('password') <p class="err">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($user->exists && ! $user->is(auth()->user()))
            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                <span>Can sign in</span>
            </label>
        @endif

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $user->exists ? 'Save' : 'Add login' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.users.index') }}">Cancel</a>
        </div>
    </form>
</section>

@endsection
