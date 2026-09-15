@props([
    'rows',              // [['label' => 'Jan', 'value' => 1234], ...]
    'valueLabel' => 'Value',
    'format' => 'money', // money | number
    'height' => 200,
])

@php
    /*
     | One series, one hue, drawn server-side as inline SVG.
     |
     | The chart is a magnitude comparison, so it is columns with a zero
     | baseline; there is no legend because there is nothing to distinguish;
     | and only the peak carries a direct label, because a value on every bar
     | is noise at twelve columns. The table underneath is not a fallback — it
     | is where anyone who wants the actual figures will look.
     */
    $rows = collect($rows)->values();
    $max = max(1, $rows->max('value'));
    $peak = $rows->search(fn ($r) => $r['value'] >= $max);

    $w = 720;
    $h = (int) $height;
    $padL = 8; $padR = 8; $padT = 26; $padB = 26;
    $plotH = $h - $padT - $padB;
    $slot = ($w - $padL - $padR) / max(1, $rows->count());
    $barW = min(24, $slot * 0.55);

    $fmt = fn ($v) => $format === 'money'
        ? \App\Support\Money::compact($v)
        : \App\Support\Money::compactNumber($v);

    $chartId = 'c'.substr(md5(uniqid('', true)), 0, 6);
@endphp

<figure class="chart" id="{{ $chartId }}">
    <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img"
         aria-label="{{ $valueLabel }} by period">

        {{-- Hairline gridlines at quarters, behind the bars. --}}
        @foreach ([0.25, 0.5, 0.75, 1] as $step)
            @php $y = $padT + $plotH - ($plotH * $step); @endphp
            <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $w - $padR }}" y2="{{ $y }}" class="chart-grid"/>
        @endforeach

        <line x1="{{ $padL }}" y1="{{ $padT + $plotH }}" x2="{{ $w - $padR }}" y2="{{ $padT + $plotH }}" class="chart-axis"/>

        @foreach ($rows as $i => $row)
            @php
                $barH = $row['value'] > 0 ? max(2, ($row['value'] / $max) * $plotH) : 0;
                $x = $padL + ($i * $slot) + (($slot - $barW) / 2);
                $y = $padT + $plotH - $barH;
            @endphp

            @if ($barH > 0)
                <rect class="chart-bar" x="{{ round($x, 1) }}" y="{{ round($y, 1) }}"
                      width="{{ round($barW, 1) }}" height="{{ round($barH, 1) }}" rx="4">
                    <title>{{ $row['label'] }} — {{ $fmt($row['value']) }}</title>
                </rect>
            @endif

            {{-- One direct label, on the peak only. --}}
            @if ($i === $peak && $row['value'] > 0)
                <text class="chart-peak" x="{{ round($x + $barW / 2, 1) }}" y="{{ round($y - 8, 1) }}"
                      text-anchor="middle">{{ $fmt($row['value']) }}</text>
            @endif

            <text class="chart-tick" x="{{ round($x + $barW / 2, 1) }}" y="{{ $h - 8 }}" text-anchor="middle">
                {{ $row['short'] ?? $row['label'] }}
            </text>
        @endforeach
    </svg>

    <figcaption class="chart-cap">
        <button class="link-toggle" type="button" data-chart-table="{{ $chartId }}">Show the figures</button>
    </figcaption>

    <div class="chart-table" hidden>
        <table class="table table-compact">
            <thead><tr><th>Period</th><th class="num">{{ $valueLabel }}</th></tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">
                            {{ $format === 'money' ? \App\Support\Money::format($row['value']) : \App\Support\Money::number($row['value']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</figure>
