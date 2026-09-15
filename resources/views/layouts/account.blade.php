@extends('layouts.storefront')

@section('body-class', 'acct-body')

@section('content')
    <div class="container acct-shell">
        <header class="acct-head">
            <div>
                <h1>@yield('heading', 'Your account')</h1>
                <p class="acct-sub">@yield('subheading')</p>
            </div>
            @hasSection('actions')
                <div class="acct-actions">@yield('actions')</div>
            @endif
        </header>

        @includeWhen(! auth()->user()->company?->canTrade(), 'partials.account-status')

        <div class="acct-grid">
            @include('partials.account-nav')
            <div class="acct-main">@yield('panel')</div>
        </div>
    </div>
@endsection
