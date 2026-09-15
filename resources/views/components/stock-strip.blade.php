@props(['product'])

{{--
    Stock is public. It is the first thing a wholesale buyer asks and the last
    thing they want to sign up to find out, so unlike price it is shown to
    everyone, signed in or not.
--}}
<div class="stock-strip">
    @foreach ($product->inventoryLevels->filter(fn ($l) => $l->warehouse?->include_in_storefront) as $level)
        @php $available = $level->available(); @endphp
        <div class="ss-item">
            <span class="ss-code mono">{{ $level->warehouse->code }}</span>
            <span class="ss-qty {{ $available > 0 ? '' : 'is-out' }}">
                {{ $available > 0 ? \App\Support\Money::number($available) : 'Backorder' }}
            </span>
            <span class="ss-meta">{{ $level->warehouse->locationLine() }}</span>
            @if ($available === 0 && $level->next_intake_on)
                <span class="ss-eta">Due {{ $level->next_intake_on->format('j M') }}</span>
            @endif
        </div>
    @endforeach
</div>
