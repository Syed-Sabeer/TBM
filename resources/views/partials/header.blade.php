@php
    $user = auth()->user();
    $nav = [
        ['key' => 'bags',      'label' => 'Bags',           'mega' => true],
        ['key' => 'wholesale', 'label' => 'Wholesale',      'href' => route('register')],
        ['key' => 'custom',    'label' => 'Customization',  'href' => route('customization')],
        ['key' => 'story',     'label' => 'Our Story',      'href' => route('story')],
        ['key' => 'sustain',   'label' => 'Sustainability', 'href' => route('sustainability')],
        ['key' => 'contact',   'label' => 'Contact',        'href' => route('contact')],
    ];
    $current = $current ?? null;
@endphp

<header class="site-header">
    <div class="container">
        <div class="header-main">
            @include('partials.logo')

            <nav aria-label="Main">
                <ul class="nav">
                    @foreach ($nav as $item)
                        @if ($item['mega'] ?? false)
                            <li class="has-mega">
                                <button type="button" aria-expanded="false">
                                    {{ $item['label'] }}<span class="nav-caret"><x-icon name="chevD"/></span>
                                </button>
                            </li>
                        @else
                            <li>
                                <a class="{{ $current === $item['key'] ? 'is-current' : '' }}" href="{{ $item['href'] }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>

            <div class="header-actions">
                <a class="icon-btn" href="{{ route('shop') }}" aria-label="Search catalog"><x-icon name="search"/></a>

                @auth
                    @include('partials.account-menu')
                @else
                    <a class="icon-btn" href="{{ route('login') }}" aria-label="Log in"><x-icon name="user"/></a>
                @endauth

                <a class="icon-btn" href="{{ route('cart.index') }}" aria-label="Cart">
                    <x-icon name="bag"/>
                    <span class="count" id="cartCount" @if (! ($cartCount ?? 0)) hidden @endif>{{ $cartCount ?? 0 }}</span>
                </a>

                <a class="btn btn-primary btn-sm header-cta" href="{{ auth()->check() ? route('account.dashboard') : route('register') }}">
                    {{ auth()->check() ? 'My account' : 'Open an account' }}
                </a>

                <button class="icon-btn burger" id="burger" aria-label="Menu"><x-icon name="burger"/></button>
            </div>
        </div>
    </div>

    @include('partials.mega-menu')
</header>
