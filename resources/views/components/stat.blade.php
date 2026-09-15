@props(['label', 'value', 'sub' => null, 'delta' => null])

<div class="stat">
    <div class="lab">{{ $label }}</div>
    <div class="val">{{ $value }}</div>

    @if ($delta !== null)
        <div class="sub">
            <span class="delta {{ $delta > 0 ? 'is-up' : ($delta < 0 ? 'is-down' : '') }}">
                {{ \App\Support\Money::delta($delta) }}
            </span>
            {{ $sub }}
        </div>
    @elseif ($sub)
        <div class="sub">{{ $sub }}</div>
    @endif
</div>
