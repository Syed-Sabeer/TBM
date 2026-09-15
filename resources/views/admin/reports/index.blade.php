@extends('layouts.admin')

@section('title', 'Reports')
@section('heading', 'Reports')
@section('subheading', 'The whole book, by month, account, item and colour.')

@section('toolbar')
    <form method="GET" class="inline-form">
        <select class="select select-sm" name="months" onchange="this.form.submit()">
            @foreach ([3 => 'Last 3 months', 6 => 'Last 6 months', 12 => 'Last 12 months', 24 => 'Last 24 months'] as $value => $label)
                <option value="{{ $value }}" @selected($months === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.reports.export', ['view' => 'customers', 'months' => $months]) }}">Export CSV</a>
@endsection

@section('content')

<div class="mini-kpi">
    <x-stat label="Revenue" :value="\App\Support\Money::compact($headline['value'])" :delta="$headline['value_change']" sub="vs the period before"/>
    <x-stat label="Pieces" :value="\App\Support\Money::compactNumber($headline['pieces'])" :delta="$headline['pieces_change']" sub="vs the period before"/>
    <x-stat label="Orders" :value="$headline['orders']" :delta="$headline['orders_change']" sub="vs the period before"/>
    <x-stat label="Average order" :value="\App\Support\Money::format($headline['average_order'])" :sub="'over '.$months.' months'"/>
</div>

<section class="panel">
    <div class="panel-head">
        <h3>Revenue by month</h3>
        <a class="link-arrow btn-sm" href="{{ route('admin.reports.export', ['view' => 'months']) }}">CSV</a>
    </div>
    <x-column-chart :rows="$byMonth" value-label="Revenue" format="money" height="240"/>
</section>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>By account</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.reports.customers', ['months' => $months]) }}">All</a>
        </div>
        <x-bar-list :rows="$byCompany->map(fn ($r) => ['label' => $r['name'], 'meta' => $r['orders'].' orders', 'value' => $r['value']])"
                    format="money" :limit="12"/>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>By item</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.reports.items', ['months' => $months]) }}">All</a>
        </div>
        <x-bar-list :rows="$byItem->map(fn ($r) => ['label' => $r['sku'], 'meta' => $r['name'], 'value' => $r['pieces']])"
                    format="number" :limit="12"/>
    </section>
</div>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head"><h3>By colour</h3></div>
        <x-bar-list :rows="$byColour->map(fn ($r) => ['label' => $r['colour'], 'value' => $r['pieces']])" format="number" :limit="12"/>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>By category</h3></div>
        <x-bar-list :rows="$byCategory->map(fn ($r) => ['label' => $r['category'], 'value' => $r['pieces']])" format="number"/>
    </section>
</div>

<div class="panel-pair">
    <section class="panel">
        <div class="panel-head"><h3>Shipped from</h3></div>
        <x-bar-list :rows="$byWarehouse->map(fn ($r) => ['label' => $r['name'], 'meta' => $r['code'], 'value' => $r['pieces']])" format="number"/>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Blank vs decorated</h3></div>
        <x-bar-list :rows="$byDecoration->map(fn ($r) => ['label' => $r['decoration'], 'value' => $r['pieces']])" format="number"/>
    </section>
</div>

@endsection
