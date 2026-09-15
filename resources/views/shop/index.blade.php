@extends('layouts.storefront')

@section('title', $category?->name ?? 'Full catalogue')
@section('meta', $category?->blurb ?? 'Every bag we stock, with live warehouse quantities.')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a>
    <x-icon name="chev"/>
    <a href="{{ route('shop') }}">Catalogue</a>
    @if ($category)
        <x-icon name="chev"/>
        <span>{{ $category->name }}</span>
    @endif
@endsection

@section('content')
<div class="container shop-shell">

    <header class="shop-head">
        <div>
            <h1>{{ $category?->name ?? 'Full catalogue' }}</h1>
            <p>{{ $category?->blurb ?? 'Every item we hold, with the quantity on the shelf right now.' }}</p>
        </div>
        <span class="shop-count">{{ number_format($products->total()) }} items</span>
    </header>

    <div class="shop-grid">

        {{-- ------------------------------------------------- Filters --- --}}
        <aside class="shop-filters">
            <form method="GET" action="{{ $category ? route('shop.category', $category) : route('shop') }}" id="filterForm">

                <div class="field">
                    <label class="label" for="q">Search</label>
                    <input class="input" id="q" type="search" name="q"
                           value="{{ $filters['q'] ?? '' }}"
                           placeholder="Item number or name">
                </div>

                <div class="field">
                    <label class="label" for="sort">Sort</label>
                    <select class="select" id="sort" name="sort" onchange="this.form.submit()">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @unless ($category)
                    <fieldset class="filter-group">
                        <legend>Category</legend>
                        @foreach ($categories as $group => $items)
                            <span class="filter-sub">{{ $group }}</span>
                            <ul class="filter-list">
                                @foreach ($items as $item)
                                    <li>
                                        <a href="{{ route('shop.category', $item) }}">
                                            {{ $item->name }} <span>{{ $item->products_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </fieldset>
                @endunless

                <fieldset class="filter-group">
                    <legend>Colour</legend>
                    <div class="swatch-filter">
                        @foreach ($colourways as $colourway)
                            <label class="swatch-pick {{ ($filters['colour'] ?? '') === $colourway->slug ? 'is-active' : '' }}"
                                   title="{{ $colourway->name }}">
                                <input type="radio" name="colour" value="{{ $colourway->slug }}"
                                       @checked(($filters['colour'] ?? '') === $colourway->slug)
                                       onchange="this.form.submit()">
                                <span class="swatch" style="background:{{ $colourway->hex_body }}"></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Ready to ship from</legend>
                    <ul class="filter-list">
                        @foreach ($warehouses as $warehouse)
                            <li>
                                <label class="check">
                                    <input type="radio" name="warehouse" value="{{ $warehouse->code }}"
                                           @checked(($filters['warehouse'] ?? '') === $warehouse->code)
                                           onchange="this.form.submit()">
                                    <span>{{ $warehouse->name }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Show</legend>
                    <label class="check">
                        <input type="checkbox" name="in_stock" value="1"
                               @checked(! empty($filters['in_stock']))
                               onchange="this.form.submit()">
                        <span>In stock only</span>
                    </label>
                    @foreach (['bestseller' => 'Bestsellers', 'new' => 'New this season', 'eco' => 'Organic &amp; recycled', 'value' => 'Lowest landed cost'] as $flag => $label)
                        <label class="check">
                            <input type="radio" name="flag" value="{{ $flag }}"
                                   @checked(($filters['flag'] ?? '') === $flag)
                                   onchange="this.form.submit()">
                            <span>{!! $label !!}</span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="filter-actions">
                    <button class="btn btn-primary btn-block btn-sm" type="submit">Apply</button>
                    <a class="btn btn-ghost btn-block btn-sm" href="{{ $category ? route('shop.category', $category) : route('shop') }}">Clear all</a>
                </div>
            </form>
        </aside>

        {{-- ------------------------------------------------- Results --- --}}
        <div class="shop-results">
            @guest
                <div class="notice notice-info shop-gate">
                    <x-icon name="lock"/>
                    <p>
                        Quantities are shown to everyone. Pricing is per account —
                        <a href="{{ route('login') }}">sign in</a> or
                        <a href="{{ route('register') }}">open an account</a> to see yours.
                    </p>
                </div>
            @endguest

            @if ($products->isEmpty())
                <div class="empty">
                    <x-icon name="search"/>
                    <h3>Nothing matches those filters</h3>
                    <p>Try widening the colour or warehouse filter, or search on an item number.</p>
                    <a class="btn btn-outline btn-sm" href="{{ route('shop') }}">Clear filters</a>
                </div>
            @else
                <div class="product-grid">
                    @foreach ($products as $product)
                        <x-product-card :product="$product"/>
                    @endforeach
                </div>

                {{ $products->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
