@extends('layouts.storefront')

@section('title', 'Wholesale tote bags, stocked in the US')

@section('content')

{{-- ------------------------------------------------------------ Hero --- --}}
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="eyebrow"><x-icon name="sparkle"/>Trade supply since 2009</span>

            <h1>Blank bags, <em>stocked</em> and ready to decorate.</h1>

            <p class="lede">
                {{ \App\Support\Money::compactNumber($totalStock) }} pieces on the shelf across
                {{ $warehouses->count() }} US warehouses, {{ $skuCount }} items, and a rate card
                written for your account rather than a public list.
            </p>

            <div class="hero-cta">
                <a class="btn btn-primary btn-lg" href="{{ route('shop') }}">
                    Browse the catalogue <x-icon name="arrow"/>
                </a>
                @guest
                    <a class="btn btn-outline btn-lg" href="{{ route('register') }}">Open an account</a>
                @endguest
            </div>

            <ul class="hero-points">
                <li><x-icon name="check"/>Stock updated every morning from the mill</li>
                <li><x-icon name="check"/>Your pricing, visible the moment you sign in</li>
                <li><x-icon name="check"/>One account, as many buyers as you need</li>
            </ul>
        </div>

        <div class="hero-art">
            @foreach ($featured->take(3) as $i => $product)
                <div class="hero-bag hero-bag-{{ $i + 1 }}">
                    {!! app(\App\Services\Rendering\BagRenderer::class)->forProduct($product) !!}
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- --------------------------------------------------------- Marquee --- --}}
<div class="marquee">
    <div class="marquee-track">
        @foreach (array_merge($warehouses->all(), $warehouses->all()) as $warehouse)
            <span>{{ $warehouse->name }}</span><i>&bull;</i>
            <span>{{ $warehouse->lead_time ?? 'Ships in 48 hours' }}</span><i>&bull;</i>
        @endforeach
    </div>
</div>

{{-- ------------------------------------------------------ Categories --- --}}
<section class="section">
    <div class="container">
        <header class="section-head">
            <div>
                <h2>Shop the catalogue</h2>
                <p>Fourteen constructions, every one held in depth.</p>
            </div>
            <a class="link-arrow" href="{{ route('shop') }}">All items <x-icon name="arrow"/></a>
        </header>

        <div class="cat-grid">
            @foreach ($categories as $category)
                <a class="cat-tile" href="{{ route('shop.category', $category) }}">
                    <div class="cat-art">
                        {!! app(\App\Services\Rendering\BagRenderer::class)->render($category->shape ?? 'tote') !!}
                    </div>
                    <div class="cat-body">
                        <h3>{{ $category->name }}</h3>
                        <span>{{ $category->products_count }} items</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- -------------------------------------------------------- Featured --- --}}
<section class="section section-tint">
    <div class="container">
        <header class="section-head">
            <div>
                <h2>Moving fastest this quarter</h2>
                <p>What distributors are reordering most.</p>
            </div>
            <a class="link-arrow" href="{{ route('shop', ['flag' => 'bestseller']) }}">All bestsellers <x-icon name="arrow"/></a>
        </header>

        <div class="product-grid">
            @foreach ($featured as $product)
                <x-product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>

{{-- ----------------------------------------------------- Stock strip --- --}}
<section class="section">
    <div class="container">
        <div class="inv-strip">
            <div class="inv-head">
                <h2>Live from the warehouse floor</h2>
                <p>These are the figures the picking team sees. They change every morning when the mill sheet lands.</p>
            </div>

            <div class="inv-sites">
                @foreach ($warehouses as $warehouse)
                    <div class="inv-site">
                        <span class="mono">{{ $warehouse->code }}</span>
                        <b>{{ \App\Support\Money::compactNumber($warehouse->unitsOnHand()) }}</b>
                        <span>{{ $warehouse->name }}</span>
                        <small>{{ $warehouse->lead_time }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ----------------------------------------------------- Value props --- --}}
<section class="section">
    <div class="container">
        <div class="feature-grid">
            <article class="feature-card">
                <x-icon name="tag"/>
                <h3>Your rate, not a list price</h3>
                <p>Every account sits on a tier, and individual items can carry a rate negotiated just for you. Sign in and the catalogue reprices itself.</p>
            </article>
            <article class="feature-card">
                <x-icon name="users"/>
                <h3>One account, many buyers</h3>
                <p>Add everyone who orders. They share the same pricing, the same addresses and the same history — no matter who placed which order.</p>
            </article>
            <article class="feature-card">
                <x-icon name="chart"/>
                <h3>Your own purchase data</h3>
                <p>Pull what you bought by item, by colour, by month. Useful at renewal time, and for the reorder you forgot you needed.</p>
            </article>
            <article class="feature-card">
                <x-icon name="printer"/>
                <h3>Decoration handled</h3>
                <p>Screen print, DTF, embroidery and sublimation, quoted per colour and location once artwork is in.</p>
            </article>
        </div>
    </div>
</section>

{{-- -------------------------------------------------------- CTA band --- --}}
@guest
<section class="cta-band">
    <div class="container">
        <div>
            <h2>Pricing opens with an account</h2>
            <p>Applications are reviewed by a person, usually within a business day. You will need a resale certificate.</p>
        </div>
        <a class="btn btn-light btn-lg" href="{{ route('register') }}">Open a wholesale account <x-icon name="arrow"/></a>
    </div>
</section>
@endguest

@endsection
