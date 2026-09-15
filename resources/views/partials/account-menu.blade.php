@php
    $user = auth()->user();
    $company = $user->company;
@endphp

<div class="acct-wrap">
    <button class="icon-btn" id="acctBtn" aria-label="Account" aria-expanded="false" aria-controls="acctMenu">
        <x-icon name="user"/>
    </button>

    <div class="acct-menu" id="acctMenu">
        <div class="head">
            <b>{{ $user->name }}</b>
            <span>
                {{ $company?->name ?? 'TBM staff' }}
                @if ($company?->tier)
                    &middot; {{ $company->tier->fullName() }}
                @endif
            </span>
        </div>

        @if ($user->isStaff())
            <a href="{{ route('admin.dashboard') }}"><span><x-icon name="grid"/></span>Back office</a>
            <a href="{{ route('admin.orders.index') }}"><span><x-icon name="doc"/></span>Orders</a>
            <a href="{{ route('admin.companies.index') }}"><span><x-icon name="users"/></span>Accounts</a>
        @else
            <a href="{{ route('account.dashboard') }}"><span><x-icon name="grid"/></span>Dashboard</a>
            <a href="{{ route('account.orders.index') }}"><span><x-icon name="doc"/></span>Order history</a>
            <a href="{{ route('account.reports') }}"><span><x-icon name="chart"/></span>Purchase reports</a>
            <a href="{{ route('account.users.index') }}"><span><x-icon name="users"/></span>Company users</a>
            <a href="{{ route('account.pricing') }}"><span><x-icon name="tag"/></span>My price list</a>
            <a href="{{ route('account.addresses.index') }}"><span><x-icon name="pin"/></span>Addresses</a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="mi" type="submit"><span><x-icon name="logout"/></span>Sign out</button>
        </form>
    </div>
</div>
