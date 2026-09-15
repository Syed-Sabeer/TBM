@extends('layouts.auth')

@section('title', 'Back office')

@section('content')
<div class="adm-login">
    <div class="adm-login-card">
        <a class="adm-brand" href="{{ route('home') }}">
            <svg class="logo-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                <rect width="40" height="40" rx="10" fill="#0E2318"/>
                <path d="M12 15h16l1 13.5a1.2 1.2 0 0 1-1.2 1.3H12.2a1.2 1.2 0 0 1-1.2-1.3Z" fill="#F7F4ED"/>
                <path d="M16 15.5c0-4 8-4 8 0" stroke="#B9793C" stroke-width="2.1" fill="none" stroke-linecap="round"/>
            </svg>
            <span><b>TBM</b><small>Back office</small></span>
        </a>

        <h1>Staff sign in</h1>
        <p class="auth-sub">Orders, accounts, stock and the morning import.</p>

        @if ($errors->any())
            <div class="notice notice-warn">
                <x-icon name="shield"/>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="auth-form">
            @csrf

            <div class="field">
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label class="label" for="password">Password</label>
                <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in <x-icon name="arrow"/></button>
        </form>

        <p class="auth-back"><a href="{{ route('login') }}">Customer sign-in is over here</a></p>
    </div>
</div>
@endsection
