@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<div class="auth-shell">

    <div class="auth-panel">
        <div class="auth-inner">
            @include('partials.logo')

            <h1>Sign in</h1>
            <p class="auth-sub">Your pricing, your order history, your reports.</p>

            @if ($errors->any())
                <div class="notice notice-warn">
                    <x-icon name="shield"/>
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="field">
                    <label class="label" for="email">Work email</label>
                    <input class="input" id="email" type="email" name="email"
                           value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>

                <div class="field">
                    <label class="label" for="password">Password</label>
                    <input class="input" id="password" type="password" name="password"
                           required autocomplete="current-password">
                </div>

                <label class="check">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in on this computer</span>
                </label>

                <button class="btn btn-primary btn-block btn-lg" type="submit">
                    Sign in <x-icon name="arrow"/>
                </button>
            </form>

            <p class="auth-alt">
                No account yet? <a href="{{ route('register') }}">Apply for wholesale pricing</a>.
            </p>

            <p class="auth-back"><a href="{{ route('home') }}">&larr; Back to the catalogue</a></p>
        </div>
    </div>

    {{-- The side panel says what signing in actually unlocks, which is the
         only reason anyone fills the form in. --}}
    <aside class="auth-aside">
        <div class="auth-aside-inner">
            <h2>What opens up</h2>
            <ul class="auth-points">
                <li><x-icon name="tag"/><div><b>Your contract rates</b><span>Not a list price. Your tier, plus anything negotiated on individual items.</span></div></li>
                <li><x-icon name="users"/><div><b>Shared order history</b><span>Every buyer on your account sees the same orders, addresses and documents.</span></div></li>
                <li><x-icon name="chart"/><div><b>Your purchase data</b><span>By item, by colour, by month — pull it whenever you need it.</span></div></li>
                <li><x-icon name="box"/><div><b>Live warehouse stock</b><span>The same figures the picking team sees, updated every morning.</span></div></li>
            </ul>
        </div>
    </aside>
</div>
@endsection
