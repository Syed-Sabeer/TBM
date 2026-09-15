@extends('layouts.storefront')

@section('title', $product->name)
@section('meta', Str::limit(strip_tags($product->description), 150))

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a>
    <x-icon name="chev"/>
    <a href="{{ route('shop') }}">Catalogue</a>
    <x-icon name="chev"/>
    <a href="{{ route('shop.category', $product->category) }}">{{ $product->category->name }}</a>
    <x-icon name="chev"/>
    <span class="mono">{{ $product->sku }}</span>
@endsection

@section('content')
<div class="container pdp">

    <div class="pdp-grid">

        {{-- ------------------------------------------------- Gallery --- --}}
        <div class="pdp-media">
            <div class="pdp-stage" id="pdpStage">
                {!! app(\App\Services\Rendering\BagRenderer::class)->forProduct($product, $product->colourways->first()) !!}
            </div>

            <div class="pdp-thumbs">
                @foreach ($product->colourways as $colourway)
                    <button class="pdp-thumb {{ $loop->first ? 'is-active' : '' }}"
                            type="button"
                            data-colour="{{ $colourway->id }}"
                            title="{{ $colourway->name }}">
                        {!! app(\App\Services\Rendering\BagRenderer::class)->forProduct($product, $colourway) !!}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- --------------------------------------------------- Buy box --- --}}
        <div class="pdp-buy">
            <span class="pdp-sku mono">{{ $product->sku }}</span>
            <h1>{{ $product->name }}</h1>

            <p class="pdp-spec">
                {{ $product->material }}
                @if ($product->fabric_weight) &middot; {{ $product->fabric_weight }} @endif
                @if ($product->origin) &middot; {{ $product->origin }} @endif
            </p>

            @if ($product->flags)
                <div class="pdp-flags">
                    @foreach ($product->flags as $flag)
                        <span class="badge">{{ Str::headline($flag) }}</span>
                    @endforeach
                </div>
            @endif

            <x-stock-strip :product="$product"/>

            <form method="POST" action="{{ route('cart.store') }}" class="pdp-form" id="pdpForm">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                {{-- Colour --}}
                <div class="field">
                    <span class="label">Colour</span>
                    <div class="swatch-row" id="colourOpts">
                        @foreach ($product->colourways as $colourway)
                            <label class="swatch-pick {{ $loop->first ? 'is-active' : '' }}" title="{{ $colourway->name }}">
                                <input type="radio" name="colourway_id" value="{{ $colourway->id }}" @checked($loop->first)>
                                <span class="swatch" style="background:{{ $colourway->hex_body }}"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Size --}}
                <div class="field">
                    <span class="label">Size</span>
                    <div class="opt-row">
                        @foreach ($product->sizes as $i => $size)
                            @php
                                $label = is_array($size) ? ($size['label'] ?? '') : $size;
                                $note = is_array($size) ? ($size['note'] ?? null) : null;
                            @endphp
                            <label class="size-opt {{ $i === 0 ? 'is-active' : '' }}">
                                <input type="radio" name="size" value="{{ $label }}" @checked($i === 0)>
                                {{ $label }}@if ($note)<small>{{ $note }}</small>@endif
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Decoration --}}
                <div class="field">
                    <span class="label">Decoration</span>
                    <div class="opt-row">
                        @foreach ($decorationMethods as $i => $method)
                            <label class="size-opt {{ $i === 0 ? 'is-active' : '' }} {{ $method['available'] ? '' : 'is-off' }}">
                                <input type="radio" name="decoration" value="{{ $method['name'] }}"
                                       @checked($i === 0) @disabled(! $method['available'])>
                                {{ $method['name'] }}<small>{{ $method['note'] }}</small>
                            </label>
                        @endforeach
                    </div>
                    <p class="hint" id="decHint">
                        Blank stock ships in 48 hours. Decoration is quoted per colour and location once artwork is in,
                        and added to your order by your rep before it is confirmed.
                    </p>
                </div>

                {{-- Quantity --}}
                <div class="field">
                    <label class="label" for="qty">Quantity</label>
                    <div class="qty-row">
                        <button class="qty-btn" type="button" data-step="-{{ $product->order_step }}" aria-label="Fewer">&minus;</button>
                        <input class="input qty-input" id="qty" type="number" name="quantity"
                               value="{{ $product->moq }}"
                               min="{{ $product->moq }}"
                               step="{{ $product->order_step }}"
                               data-quote="{{ route('product.quote', $product) }}">
                        <button class="qty-btn" type="button" data-step="{{ $product->order_step }}" aria-label="More">+</button>
                    </div>
                    <p class="hint">
                        Minimum {{ number_format($product->moq) }} pieces, in steps of {{ number_format($product->order_step) }}.
                        Cartons hold {{ number_format($product->carton_quantity) }}.
                    </p>
                </div>

                {{-- Price --}}
                <div class="pdp-price" id="pdpPrice">
                    <x-price :product="$product" :quantity="$product->moq" :show-from="false" size="lg"/>
                </div>

                @auth
                    @can('create', App\Models\Order::class)
                        <button class="btn btn-primary btn-lg btn-block" type="submit">
                            Add to basket <x-icon name="arrow"/>
                        </button>
                    @else
                        <div class="notice notice-warn">
                            <x-icon name="lock"/>
                            <p>
                                @if (! auth()->user()->company?->canTrade())
                                    Ordering opens when your account is approved.
                                @else
                                    Your access is view-only. An account admin on {{ auth()->user()->company->name }} can change that.
                                @endif
                            </p>
                        </div>
                    @endcan
                @else
                    <a class="btn btn-primary btn-lg btn-block" href="{{ route('login') }}">
                        Sign in for your price <x-icon name="arrow"/>
                    </a>
                @endauth

                <p class="pdp-note">FOB warehouse. Freight and decoration are confirmed on your acknowledgement.</p>
            </form>
        </div>
    </div>

    {{-- --------------------------------------------------------- Tabs --- --}}
    <div class="pdp-tabs">
        <div class="tabbar" role="tablist">
            <button class="tab is-active" data-tab="detail" role="tab" aria-selected="true">Details</button>
            <button class="tab" data-tab="ladder" role="tab" aria-selected="false">Quantity breaks</button>
            <button class="tab" data-tab="imprint" role="tab" aria-selected="false">Imprint &amp; decoration</button>
            <button class="tab" data-tab="ship" role="tab" aria-selected="false">Shipping</button>
        </div>

        <section class="tab-panel is-active" data-panel="detail">
            <div class="prose">{!! nl2br(e($product->description)) !!}</div>

            <table class="table table-compact spec-table">
                <tbody>
                    <tr><th>Item number</th><td class="mono">{{ $product->sku }}</td></tr>
                    <tr><th>Material</th><td>{{ $product->material }}</td></tr>
                    @if ($product->fabric_weight)
                        <tr><th>Fabric weight</th><td>{{ $product->fabric_weight }}</td></tr>
                    @endif
                    <tr><th>Sizes</th><td>{{ implode(' · ', $product->sizeLabels()) }}</td></tr>
                    <tr><th>Colours</th><td>{{ $product->colourways->pluck('name')->join(', ') }}</td></tr>
                    <tr><th>Minimum</th><td>{{ number_format($product->moq) }} pieces</td></tr>
                    <tr><th>Carton</th><td>{{ number_format($product->carton_quantity) }} pieces</td></tr>
                    @if ($product->origin)
                        <tr><th>Origin</th><td>{{ $product->origin }}</td></tr>
                    @endif

                    {{-- The mill reference, staff only. A customer never sees this row. --}}
                    @can('see-mill-reference')
                        <tr class="row-staff">
                            <th>Mill reference</th>
                            <td class="mono">{{ $product->parent_sku }} <span class="badge">Internal</span></td>
                        </tr>
                    @endcan
                </tbody>
            </table>
        </section>

        <section class="tab-panel" data-panel="ladder">
            @if ($ladder)
                <p class="panel-intro">Your rate at each break. The larger the run, the lower the piece price.</p>
                <table class="table ladder-table">
                    <thead>
                        <tr><th>Quantity</th><th class="num">Your price</th><th class="num">Extended</th><th class="num">Saving</th></tr>
                    </thead>
                    <tbody>
                        @php $first = $ladder->first()->unitPrice; @endphp
                        @foreach ($ladder as $break)
                            <tr>
                                <td>{{ number_format($break->quantity) }}+</td>
                                <td class="num"><b>{{ \App\Support\Money::unit($break->unitPrice) }}</b></td>
                                <td class="num muted">{{ \App\Support\Money::format($break->lineTotal()) }}</td>
                                <td class="num">
                                    @php $off = $break->savingPercentAgainst($first); @endphp
                                    {{ $off > 0.5 ? '−'.number_format($off, 0).'%' : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">
                    <x-icon name="lock"/>
                    <h3>Quantity breaks are per account</h3>
                    <p>Two accounts buying the same bag at the same quantity often pay different rates. Sign in to see yours.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Sign in</a>
                </div>
            @endif
        </section>

        <section class="tab-panel" data-panel="imprint">
            <div class="method-grid">
                @foreach ($decorationMethods as $method)
                    <article class="method-card {{ $method['available'] ? '' : 'is-off' }}">
                        <h4>{{ $method['name'] }}</h4>
                        <p>{{ $method['note'] }}</p>
                        @unless ($method['available'])
                            <span class="badge badge-warn">Not available on this fabric</span>
                        @endunless
                    </article>
                @endforeach
            </div>
            <p class="panel-note">
                Send artwork to {{ config('tbm.company.art_email') }} as vector where you can.
                We will return a digital proof before anything goes on press.
            </p>
        </section>

        <section class="tab-panel" data-panel="ship">
            <x-stock-strip :product="$product"/>
            <p class="panel-note">
                Blank stock ships within 48 hours of a confirmed order. Decorated goods run on the decoration lead time
                quoted with your proof. Split shipments across warehouses are fine — say so in the order notes.
            </p>
        </section>
    </div>

    {{-- ------------------------------------------------------ Related --- --}}
    @if ($related->isNotEmpty())
        <section class="section">
            <header class="section-head">
                <div><h2>Also in {{ $product->category->name }}</h2></div>
                <a class="link-arrow" href="{{ route('shop.category', $product->category) }}">See all <x-icon name="arrow"/></a>
            </header>
            <div class="product-grid">
                @foreach ($related as $item)
                    <x-product-card :product="$item"/>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
