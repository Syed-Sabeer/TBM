@php
    $user = auth()->user();

    $items = [
        ['route' => 'account.dashboard',      'icon' => 'grid',  'label' => 'Overview'],
        ['route' => 'account.orders.index',   'icon' => 'doc',   'label' => 'Orders',        'badge' => $navBadges['orders'] ?? 0],
        ['route' => 'account.reports',        'icon' => 'chart', 'label' => 'Purchase reports'],
        ['route' => 'account.pricing',        'icon' => 'tag',   'label' => 'My price list'],
        ['route' => 'account.inventory',      'icon' => 'box',   'label' => 'Stock watch'],
        ['route' => 'account.addresses.index','icon' => 'pin',   'label' => 'Addresses'],
        ['route' => 'account.users.index',    'icon' => 'users', 'label' => 'Company users'],
        ['route' => 'account.settings',       'icon' => 'shield','label' => 'Settings'],
    ];
@endphp

<nav class="acct-nav" aria-label="Account">
    <div class="acct-nav-card">
        <span class="acct-nav-co">{{ $user->company?->name }}</span>
        <span class="acct-nav-num mono">{{ $user->company?->account_number }}</span>
        @if ($user->company?->tier)
            <span class="badge">{{ $user->company->tier->fullName() }}</span>
        @endif
    </div>

    <ul>
        @foreach ($items as $item)
            <li>
                <a href="{{ route($item['route']) }}"
                   class="{{ request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'is-active' : '' }}">
                    <x-icon :name="$item['icon']"/>
                    <span>{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <em class="nav-badge">{{ $item['badge'] }}</em>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    {{--
        The role note. It exists because the most common support call on a
        multi-user wholesale account is "why can't I place the order" — and the
        answer is almost always the role, not a fault.
    --}}
    <div class="acct-nav-foot">
        <span class="label">Your access</span>
        <b>{{ $user->companyRole()?->label() ?? 'Contact' }}</b>
        <p>{{ $user->companyRole()?->description() }}</p>
    </div>
</nav>
