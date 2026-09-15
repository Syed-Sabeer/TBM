@extends('layouts.storefront')

@section('title', 'Sustainability')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a><x-icon name="chev"/><span>Sustainability</span>
@endsection

@section('content')
<div class="container page-shell">

    {{--
        TODO before launch: replace every certification claim below with real
        licence numbers, scope statements and current audit dates, or remove it.
        An unsubstantiated GOTS or GRS claim is a legal problem, not just a
        marketing one — this page must be checked by whoever holds the
        certificates before it goes live.
    --}}

    <header class="page-hero">
        <span class="eyebrow"><x-icon name="leaf"/>Materials and mills</span>
        <h1>Claims we can show you the paperwork for.</h1>
        <p class="lede">
            A reusable bag is only better than the alternative if it actually gets reused, and only
            credible if the certificate behind it is real and current. Here is what we hold and what
            it covers.
        </p>
    </header>

    <section class="section" id="certifications">
        <h2>Certifications</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <x-icon name="leaf"/>
                <h3>Organic cotton programme</h3>
                <p>Certified organic cotton lines run under a scope certificate held at the mill. Ask your rep for the current certificate and licence number for a specific item — we will send it.</p>
            </article>
            <article class="feature-card">
                <x-icon name="refresh"/>
                <h3>Recycled content</h3>
                <p>Recycled polyester and rPET lines are covered by transaction certificates per shipment. We can supply the TC for the lot your order ships from.</p>
            </article>
            <article class="feature-card">
                <x-icon name="shield"/>
                <h3>Social compliance</h3>
                <p>Our mills are audited for working conditions. Audit reports are available to accounts on request under NDA.</p>
            </article>
        </div>

        <div class="notice notice-info">
            <x-icon name="doc"/>
            <p>
                We do not publish blanket claims. If an item is certified, its certificate covers that item
                and that lot — and we will show it to you rather than ask you to take it on trust.
            </p>
        </div>
    </section>

    <section class="section section-tint">
        <h2>What we will not say</h2>
        <div class="prose">
            <p>
                We do not describe bags as carbon neutral, plastic free, or biodegradable. Cotton is
                water-intensive; a non-woven polypropylene bag is plastic; a jute bag is not compostable
                once it has been printed with plastisol. Those are the honest facts, and a buyer
                explaining a purchase to their own client is better served by them than by a slogan.
            </p>
            <p>
                What we will say: the goods are made to be used hundreds of times, we hold them in depth
                so they ship by ground rather than by air, and we can document the fibre in the ones that
                are certified.
            </p>
        </div>
    </section>

    <section class="section">
        <h2>Practical things that matter more</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <x-icon name="truck"/>
                <h3>Ground, not air</h3>
                <p>Stock held in three US warehouses means most orders move by ground. Air freight on a rush is the single biggest footprint in this trade.</p>
            </article>
            <article class="feature-card">
                <x-icon name="box"/>
                <h3>Right quantity</h3>
                <p>We would rather you ordered the quantity you will use. Overprinting for a break that ends up in a storeroom helps nobody.</p>
            </article>
            <article class="feature-card">
                <x-icon name="thread"/>
                <h3>Built to survive</h3>
                <p>Reinforced handle stitching and honest fabric weights. A bag that fails in a month was never the sustainable option.</p>
            </article>
        </div>
    </section>
</div>
@endsection
