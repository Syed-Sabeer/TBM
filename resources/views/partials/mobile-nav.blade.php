<div class="mobile-nav" id="mobileNav">
    <div class="row-between">
        @include('partials.logo')
        <button class="icon-btn" id="mobClose" aria-label="Close"><x-icon name="x"/></button>
    </div>

    @foreach ($navCategories ?? [] as $group => $categories)
        <details>
            <summary>{{ $group }}<span><x-icon name="chevD"/></span></summary>
            <ul>
                @foreach ($categories as $category)
                    <li><a href="{{ route('shop.category', $category) }}">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </details>
    @endforeach

    <ul>
        <li><a href="{{ route('shop') }}">Full catalog</a></li>
        <li><a href="{{ route('register') }}">Wholesale account</a></li>
        <li><a href="{{ route('customization') }}">Customization</a></li>
        <li><a href="{{ route('sustainability') }}">Sustainability</a></li>
        <li><a href="{{ route('contact') }}">Contact</a></li>
    </ul>

    <div style="margin-top:24px;display:grid;gap:10px">
        @auth
            <a class="btn btn-outline btn-block" href="{{ route('account.dashboard') }}">My account</a>
            <a class="btn btn-primary btn-block" href="{{ route('shop') }}">Start an order</a>
        @else
            <a class="btn btn-outline btn-block" href="{{ route('login') }}">Log in</a>
            <a class="btn btn-primary btn-block" href="{{ route('register') }}">Open an account</a>
        @endauth
    </div>
</div>
