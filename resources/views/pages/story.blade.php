@extends('layouts.storefront')

@section('title', 'Our story')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a><x-icon name="chev"/><span>Our Story</span>
@endsection

@section('content')
<div class="container page-shell">

    {{--
        TODO before launch: every figure and date on this page is placeholder
        copy. Replace with the real founding year, headcount, warehouse square
        footage and mill relationships, or delete the claim.
    --}}

    <header class="page-hero">
        <span class="eyebrow"><x-icon name="sparkle"/>About TBM</span>
        <h1>We keep the boring part stocked.</h1>
        <p class="lede">
            Decorators and distributors do not lose deals on design. They lose them on a bag that was
            supposed to be on the shelf and was not. That is the whole business we are in.
        </p>
    </header>

    <section class="section">
        <div class="split">
            <div class="prose">
                <h2>What we actually do</h2>
                <p>
                    We buy blank bags in depth from a small number of audited mills, hold them in US
                    warehouses, and sell them to the trade at a rate negotiated per account. No retail,
                    no public price list, no drop-shipping to consumers.
                </p>
                <p>
                    The hard part is not sourcing. It is knowing, at nine in the morning, exactly how many
                    of an item are on which shelf — and having that number be right when a decorator
                    commits to an end client. Our stock figures come straight from the mill sheet and the
                    warehouse floor, and they are the same numbers our own picking team works from.
                </p>

                <h2>Why pricing sits behind a login</h2>
                <p>
                    Because there is no single price. An account that takes a container a quarter should
                    not pay what a first order pays, and publishing a list would either punish the first
                    or give away the second. So the catalogue is open, the stock is open, and the number
                    is yours.
                </p>
            </div>

            <div class="split-art">
                {!! app(\App\Services\Rendering\BagRenderer::class)->render('jute') !!}
            </div>
        </div>
    </section>

    <section class="section section-tint">
        <h2>How we work</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <x-icon name="box"/>
                <h3>Stock first</h3>
                <p>We would rather carry an item too deep than quote a lead time. Depth is the product.</p>
            </article>
            <article class="feature-card">
                <x-icon name="users"/>
                <h3>People answer</h3>
                <p>Every account has a named manager. Complicated orders get a phone call, not a ticket.</p>
            </article>
            <article class="feature-card">
                <x-icon name="shield"/>
                <h3>Audited mills</h3>
                <p>We work with a short list of factories we have visited, and we do not switch on price alone.</p>
            </article>
            <article class="feature-card">
                <x-icon name="clock"/>
                <h3>Honest dates</h3>
                <p>If something is short, the acknowledgement says so before you promise your client anything.</p>
            </article>
        </div>
    </section>

    <section class="section" id="careers">
        <div class="split">
            <div>
                <h2>Working here</h2>
                <p>
                    Most of the team is in the warehouse or on the phone. If you know the trade and want
                    to work somewhere that treats a wrong stock figure as a serious problem, write to us.
                </p>
                <a class="btn btn-outline" href="mailto:{{ config('tbm.company.email') }}">Get in touch <x-icon name="arrow"/></a>
            </div>
        </div>
    </section>
</div>
@endsection
