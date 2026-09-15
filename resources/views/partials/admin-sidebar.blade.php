@php
    /*
     | Each item names the permission that governs it, and the sidebar simply
     | does not draw what this login cannot reach. The route middleware is
     | still the actual guard — this only stops the navigation offering a door
     | that will not open.
     */
    $sections = [
        'Today' => [
            ['route' => 'admin.dashboard',        'icon' => 'grid',    'label' => 'Overview'],
            ['route' => 'admin.orders.index',     'icon' => 'doc',     'label' => 'Orders',    'can' => 'orders.view',    'badge' => $navBadges['orders'] ?? 0],
            ['route' => 'admin.imports.index',    'icon' => 'refresh', 'label' => 'Imports',   'can' => 'imports.run',    'badge' => $navBadges['imports'] ?? 0],
        ],
        'Customers' => [
            ['route' => 'admin.companies.index',  'icon' => 'users',   'label' => 'Accounts',  'can' => 'companies.view', 'badge' => $navBadges['companies'] ?? 0],
            ['route' => 'admin.pricing.index',    'icon' => 'tag',     'label' => 'Rate cards','can' => 'pricing.view'],
        ],
        'Goods' => [
            ['route' => 'admin.products.index',   'icon' => 'box',     'label' => 'Catalogue'],
            ['route' => 'admin.inventory.index',  'icon' => 'truck',   'label' => 'Stock',     'can' => 'inventory.view'],
            ['route' => 'admin.warehouses.index', 'icon' => 'pin',     'label' => 'Warehouses','can' => 'inventory.manage'],
        ],
        'Insight' => [
            ['route' => 'admin.reports.index',    'icon' => 'chart',   'label' => 'Reports',   'can' => 'reports.view'],
            ['route' => 'admin.activity',         'icon' => 'clock',   'label' => 'Activity',  'can' => 'activity.view'],
            ['route' => 'admin.users.index',      'icon' => 'shield',  'label' => 'Staff',     'can' => 'users.manage'],
        ],
    ];
@endphp

<aside class="adm-side">
    <a class="adm-brand" href="{{ route('admin.dashboard') }}">
        <svg class="logo-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">
            <rect width="40" height="40" rx="10" fill="#0E2318"/>
            <path d="M12 15h16l1 13.5a1.2 1.2 0 0 1-1.2 1.3H12.2a1.2 1.2 0 0 1-1.2-1.3Z" fill="#F7F4ED"/>
            <path d="M16 15.5c0-4 8-4 8 0" stroke="#B9793C" stroke-width="2.1" fill="none" stroke-linecap="round"/>
        </svg>
        <span><b>TBM</b><small>Back office</small></span>
    </a>

    <nav aria-label="Back office">
        @foreach ($sections as $title => $items)
            @php
                $visible = array_filter($items, fn ($i) => ! isset($i['can']) || auth()->user()->can($i['can']));
            @endphp

            @if ($visible)
                <span class="adm-side-label">{{ $title }}</span>
                <ul>
                    @foreach ($visible as $item)
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
            @endif
        @endforeach
    </nav>

    <a class="adm-side-foot" href="{{ route('home') }}" target="_blank" rel="noopener">
        <x-icon name="arrow"/> View the storefront
    </a>
</aside>
