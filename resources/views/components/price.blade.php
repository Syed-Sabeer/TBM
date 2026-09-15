@props([
    'product',
    'quantity' => null,
    'showFrom' => true,
    'size' => null,
])

@php
    /*
     | The single gate.
     |
     | No view in this application formats a price itself. They all render this
     | component, which asks the one question — may this person see a figure? —
     | and either prints it or prints the invitation to sign in. There is
     | therefore no page where a price can leak because a condition was
     | forgotten in a template.
     */
    $user = auth()->user();
    $maySee = $user?->canSeePricing() ?? false;
    $pricing = app(\App\Services\Pricing\PricingService::class);
    $quantity = $quantity ?: ($product->moq ?: $pricing->firstBreakQuantity());
@endphp

@if ($maySee)
    @php $quote = $pricing->quote($product, $user->company, (int) $quantity); @endphp

    <div class="price {{ $size ? 'price-'.$size : '' }}">
        @if ($showFrom)
            <span class="price-from">from</span>
        @endif
        <b>{{ \App\Support\Money::unit($quote->unitPrice) }}</b>
        <span class="price-unit">/ pc at {{ \App\Support\Money::number($quantity) }}</span>

        @if ($quote->hasNegotiatedRate())
            <span class="badge badge-ok" title="A rate negotiated for your account">Your rate</span>
        @endif
    </div>
@elseif ($user)
    {{-- Signed in, but the account is not yet cleared to trade. --}}
    <div class="price price-locked">
        <x-icon name="lock"/>
        <span>Pricing opens when your account is approved</span>
    </div>
@else
    <a class="price price-locked" href="{{ route('login') }}">
        <x-icon name="lock"/>
        <span>Sign in for your price</span>
    </a>
@endif
