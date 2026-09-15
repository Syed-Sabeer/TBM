@props([
    'rows',                 // [['label' => ..., 'value' => ..., 'meta' => ...], ...]
    'format' => 'number',
    'limit' => 8,
])

@php
    /*
     | A ranked list with a proportional track behind each row. Better than a
     | pie or a donut for "which of these is biggest", and it keeps the labels
     | horizontal and readable at any width.
     */
    $rows = collect($rows)->take($limit)->values();
    $max = max(1, $rows->max('value'));

    $fmt = fn ($v) => $format === 'money'
        ? \App\Support\Money::compact($v)
        : \App\Support\Money::compactNumber($v);
@endphp

<ul class="barlist">
    @foreach ($rows as $row)
        <li>
            <span class="barlist-label">
                {{ $row['label'] }}
                @isset($row['meta'])
                    <em>{{ $row['meta'] }}</em>
                @endisset
            </span>
            <span class="barlist-track">
                <span class="barlist-fill" style="width:{{ round(($row['value'] / $max) * 100, 1) }}%"></span>
            </span>
            <span class="barlist-value">{{ $fmt($row['value']) }}</span>
        </li>
    @endforeach

    @if ($rows->isEmpty())
        <li class="barlist-empty">Nothing in this period yet.</li>
    @endif
</ul>
