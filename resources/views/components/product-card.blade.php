@props(['product', 'colourway' => null])

@php
    $colourway ??= $product->colourways->first();
    $stock = $product->totalStock();
@endphp

<article class="product-card">
    <a class="pc-media" href="{{ route('product', $product) }}" tabindex="-1" aria-hidden="true">
        {!! app(\App\Services\Rendering\BagRenderer::class)->forProduct($product, $colourway) !!}

        @if ($product->hasFlag('new'))
            <span class="pc-flag">New</span>
        @elseif ($product->hasFlag('bestseller'))
            <span class="pc-flag pc-flag-alt">Bestseller</span>
        @endif
    </a>

    <div class="pc-body">
        {{-- The customer item number. The mill reference never appears here. --}}
        <span class="pc-sku mono">{{ $product->sku }}</span>

        <h3><a href="{{ route('product', $product) }}">{{ $product->name }}</a></h3>

        <p class="pc-spec">{{ $product->material }}@if ($product->fabric_weight) &middot; {{ $product->fabric_weight }}@endif</p>

        {{-- Stock is public: it is the question every buyer asks first. --}}
        <p class="pc-stock {{ $stock > 0 ? '' : 'is-out' }}">
            @if ($stock > 0)
                <span class="dot"></span>{{ \App\Support\Money::compactNumber($stock) }} in stock
            @else
                <span class="dot"></span>Backorder
            @endif
        </p>

        <x-price :product="$product"/>

        <div class="pc-colours">
            @foreach ($product->colourways->take(6) as $swatch)
                <span class="swatch" style="background:{{ $swatch->hex_body }}" title="{{ $swatch->name }}"></span>
            @endforeach
            @if ($product->colourways->count() > 6)
                <span class="swatch-more">+{{ $product->colourways->count() - 6 }}</span>
            @endif
        </div>
    </div>
</article>
