@extends('layouts.storefront')

@section('title', 'Your basket')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a>
    <x-icon name="chev"/>
    <span>Basket</span>
@endsection

@section('content')
<div class="container cart-shell">

    <header class="page-head">
        <h1>Your basket</h1>
        @if ($lines->isNotEmpty())
            <p>{{ $lines->count() }} {{ Str::plural('line', $lines->count()) }} &middot; {{ number_format($totals->pieces) }} pieces</p>
        @endif
    </header>

    @if ($lines->isEmpty())
        <div class="empty empty-lg">
            <x-icon name="bag"/>
            <h3>Nothing in the basket yet</h3>
            <p>Add items from the catalogue and they will collect here.</p>
            <a class="btn btn-primary" href="{{ route('shop') }}">Browse the catalogue</a>
        </div>
    @else
        <div class="cart-grid">

            <div class="cart-lines">
                @foreach ($lines as $line)
                    <article class="cart-line">
                        <div class="cl-media">
                            {!! app(\App\Services\Rendering\BagRenderer::class)->forProduct($line->product, $line->colourway) !!}
                        </div>

                        <div class="cl-body">
                            <span class="mono cl-sku">{{ $line->product->sku }}</span>
                            <h3><a href="{{ route('product', $line->product) }}">{{ $line->product->name }}</a></h3>
                            <p class="cl-opts">
                                {{ $line->colourName() }} &middot; {{ $line->size }} &middot; {{ $line->decoration }}
                            </p>

                            @foreach ($line->warnings() as $warning)
                                <p class="cl-warn {{ $warning['level'] === 'warn' ? 'is-warn' : '' }}">
                                    <x-icon name="{{ $warning['level'] === 'warn' ? 'shield' : 'check' }}"/>
                                    {{ $warning['message'] }}
                                </p>
                            @endforeach
                        </div>

                        <form class="cl-qty" method="POST" action="{{ route('cart.update', $line->key) }}">
                            @csrf
                            @method('PATCH')
                            <label class="label" for="q{{ $line->key }}">Quantity</label>
                            <input class="input" id="q{{ $line->key }}" type="number" name="quantity"
                                   value="{{ $line->quantity }}"
                                   min="0" step="{{ $line->product->order_step }}"
                                   onchange="this.form.submit()">
                        </form>

                        <div class="cl-money">
                            @pricing
                                <b>{{ \App\Support\Money::format($line->lineTotal()) }}</b>
                                <span>{{ \App\Support\Money::unit($line->unitPrice()) }} / pc</span>
                            @else
                                <span class="muted">Sign in for pricing</span>
                            @endpricing
                        </div>

                        <form method="POST" action="{{ route('cart.destroy', $line->key) }}" class="cl-remove">
                            @csrf
                            @method('DELETE')
                            <button class="icon-btn" type="submit" aria-label="Remove line"><x-icon name="x"/></button>
                        </form>
                    </article>
                @endforeach

                <form method="POST" action="{{ route('cart.clear') }}" class="cart-clear">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-ghost btn-sm" type="submit">Empty the basket</button>
                </form>
            </div>

            {{-- --------------------------------------------- Summary --- --}}
            <aside class="cart-summary">
                <h3>Summary</h3>

                @pricing
                    <dl class="sum-list">
                        <div><dt>Merchandise</dt><dd>{{ \App\Support\Money::format($totals->merchandise) }}</dd></div>
                        <div>
                            <dt>Freight <small>estimate</small></dt>
                            <dd>{{ $totals->freight > 0 ? \App\Support\Money::format($totals->freight) : 'Free' }}</dd>
                        </div>
                        <div><dt>Decoration</dt><dd class="muted">Quoted after artwork</dd></div>
                        <div><dt>Tax</dt><dd class="muted">Per certificate on file</dd></div>
                        <div class="sum-total"><dt>Estimated total</dt><dd>{{ \App\Support\Money::format($totals->estimatedTotal()) }}</dd></div>
                    </dl>

                    @unless ($totals->qualifiesForFreeFreight())
                        <p class="sum-nudge">
                            <x-icon name="truck"/>
                            {{ \App\Support\Money::format($totals->freeFreightShortfall()) }} more and freight is on us.
                        </p>
                    @endunless
                @else
                    <div class="notice notice-info">
                        <x-icon name="lock"/>
                        <p>Pricing shows once you are signed in on an approved account.</p>
                    </div>
                @endpricing

                @auth
                    @can('create', App\Models\Order::class)
                        <a class="btn btn-primary btn-block btn-lg" href="{{ route('checkout.show') }}">
                            Continue to checkout <x-icon name="arrow"/>
                        </a>
                    @else
                        <p class="hint">This login cannot submit orders. An account admin can change that under Company users.</p>
                    @endcan
                @else
                    <a class="btn btn-primary btn-block btn-lg" href="{{ route('login') }}">Sign in to check out</a>
                @endauth

                <a class="btn btn-ghost btn-block btn-sm" href="{{ route('shop') }}">Keep shopping</a>

                <p class="sum-note">
                    No card is taken here. Orders are placed against your account terms and confirmed by a rep.
                </p>
            </aside>
        </div>
    @endif
</div>
@endsection
