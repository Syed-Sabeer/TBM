@extends('layouts.account')

@section('title', 'Purchase reports')
@section('heading', 'Purchase reports')
@section('subheading', 'What you bought, by item, colour, category and month.')

@section('actions')
    <form method="GET" class="inline-form">
        <select class="select" name="months" onchange="this.form.submit()">
            @foreach ([3 => 'Last 3 months', 6 => 'Last 6 months', 12 => 'Last 12 months', 24 => 'Last 24 months'] as $value => $label)
                <option value="{{ $value }}" @selected($months === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <a class="btn btn-outline btn-sm" href="{{ route('account.reports.export', ['view' => 'item', 'months' => $months]) }}">Export CSV</a>
@endsection

@section('panel')

<div class="mini-kpi">
    <x-stat label="Spend" :value="\App\Support\Money::compact($headline['value'])" :delta="$headline['value_change']" sub="vs the period before"/>
    <x-stat label="Pieces" :value="\App\Support\Money::compactNumber($headline['pieces'])" :delta="$headline['pieces_change']" sub="vs the period before"/>
    <x-stat label="Orders" :value="$headline['orders']" :delta="$headline['orders_change']" sub="vs the period before"/>
    <x-stat label="Average order" :value="\App\Support\Money::format($headline['average_order'])" :sub="'over '.$months.' months'"/>
</div>

<section class="panel">
    <div class="panel-head">
        <h3>Month by month</h3>
        <a class="link-arrow btn-sm" href="{{ route('account.reports.export', ['view' => 'month', 'months' => $months]) }}">CSV</a>
    </div>
    <x-column-chart :rows="$byMonth" value-label="Spend" format="money" height="220"/>
</section>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>By item</h3>
            <a class="link-arrow btn-sm" href="{{ route('account.reports.export', ['view' => 'item', 'months' => $months]) }}">CSV</a>
        </div>

        <div class="table-wrap">
            <table class="table table-compact">
                <thead><tr><th>Item</th><th>Description</th><th class="num">Pieces</th><th class="num">Spend</th></tr></thead>
                <tbody>
                    @forelse ($byItem as $row)
                        <tr>
                            <td class="mono nw">{{ $row['sku'] }}</td>
                            <td><span class="trunc" style="max-width:200px">{{ $row['name'] }}</span></td>
                            <td class="num">{{ number_format($row['pieces']) }}</td>
                            <td class="num">{{ \App\Support\Money::format($row['value']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Nothing in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>By colour</h3>
            <a class="link-arrow btn-sm" href="{{ route('account.reports.export', ['view' => 'colour', 'months' => $months]) }}">CSV</a>
        </div>
        <x-bar-list :rows="$byColour->map(fn ($r) => ['label' => $r['colour'], 'value' => $r['pieces']])"
                    format="number" :limit="12"/>
    </section>
</div>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head"><h3>By category</h3></div>
        <x-bar-list :rows="$byCategory->map(fn ($r) => ['label' => $r['category'], 'value' => $r['pieces']])" format="number"/>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Blank vs decorated</h3></div>
        <x-bar-list :rows="$byDecoration->map(fn ($r) => ['label' => $r['decoration'], 'value' => $r['pieces']])" format="number"/>
    </section>
</div>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>Who ordered</h3>
            <a class="link-arrow btn-sm" href="{{ route('account.reports.export', ['view' => 'user', 'months' => $months]) }}">CSV</a>
        </div>
        <x-bar-list :rows="$byUser->map(fn ($r) => ['label' => $r['name'], 'meta' => $r['orders'].' orders', 'value' => $r['value']])"
                    format="money"/>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Shipped from</h3></div>
        <x-bar-list :rows="$byWarehouse->map(fn ($r) => ['label' => $r['name'], 'meta' => $r['code'], 'value' => $r['pieces']])"
                    format="number"/>
    </section>
</div>

@endsection
