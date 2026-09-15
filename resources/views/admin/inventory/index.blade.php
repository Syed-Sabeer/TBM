@extends('layouts.admin')

@section('title', 'Stock')
@section('heading', 'Stock')
@section('subheading', 'What a signed-in customer sees on the item page. The morning import writes straight into this table.')

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.imports.create') }}">Run an import</a>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.inventory.export') }}">Export CSV</a>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.inventory.movements') }}">Movement log</a>
@endsection

@section('content')

<div class="mini-kpi">
    <x-stat label="Units on hand" :value="\App\Support\Money::compactNumber($totals['units'])" :sub="'across '.$warehouses->count().' warehouses'"/>
    <x-stat label="Value at cost" :value="\App\Support\Money::compact($totals['value'])" sub="FOB valuation"/>
    <x-stat label="Under 10 weeks cover" :value="$totals['low']" sub="items to reorder"/>
    <x-stat label="Out somewhere" :value="$totals['out']" sub="zero in at least one site"/>
</div>

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Item number, mill reference or name">
        </div>
        <div class="field">
            <label class="label" for="view">Show</label>
            <select class="select" id="view" name="view" onchange="this.form.submit()">
                <option value="">Everything</option>
                <option value="low" @selected(($filters['view'] ?? '') === 'low')>Under 10 weeks cover</option>
                <option value="out" @selected(($filters['view'] ?? '') === 'out')>Out of stock somewhere</option>
                <option value="deep" @selected(($filters['view'] ?? '') === 'deep')>Over 6 months cover</option>
            </select>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
    </form>

    @can('inventory.manage')
        <form method="POST" action="{{ route('admin.inventory.update') }}" id="stockForm">
            @csrf
            @method('PATCH')
    @endcan

        <div class="panel-head">
            <h3>{{ $rows->count() }} {{ Str::plural('item', $rows->count()) }}</h3>
            @can('inventory.manage')
                <span class="small muted">Type a corrected quantity and save — every change is logged against your name.</span>
            @endcan
        </div>

        <div class="table-wrap">
            <table class="table adm-table">
                <thead>
                    <tr>
                        <th>Item</th><th>Description</th>
                        @foreach ($warehouses as $warehouse)
                            <th class="num" title="{{ $warehouse->name }}">{{ $warehouse->code }}</th>
                        @endforeach
                        <th class="num">Total</th><th class="num">Monthly use</th><th>Cover</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        @php $product = $row['product']; $cover = $row['cover']; @endphp
                        <tr>
                            <td class="nw">
                                <a class="lk mono" href="{{ route('admin.products.edit', $product) }}">{{ $product->sku }}</a>
                                {{-- The mill reference: what a PO for this item goes out against. --}}
                                <div class="sku-parent">{{ $product->parent_sku }}</div>
                            </td>
                            <td><span class="trunc" style="max-width:200px">{{ $product->name }}</span></td>

                            @foreach ($warehouses as $j => $warehouse)
                                <td class="num">
                                    @can('inventory.manage')
                                        <input type="hidden" name="changes[{{ $i }}_{{ $j }}][product_id]" value="{{ $product->id }}">
                                        <input type="hidden" name="changes[{{ $i }}_{{ $j }}][warehouse_id]" value="{{ $warehouse->id }}">
                                        <input class="cell-edit" type="number" min="0"
                                               name="changes[{{ $i }}_{{ $j }}][quantity]"
                                               value="{{ $product->stockAt($warehouse) }}"
                                               data-original="{{ $product->stockAt($warehouse) }}">
                                    @else
                                        {{ number_format($product->stockAt($warehouse)) }}
                                    @endcan
                                </td>
                            @endforeach

                            <td class="num"><b>{{ number_format($product->totalStock()) }}</b></td>
                            <td class="num muted">{{ number_format($row['monthly']) }}</td>
                            <td class="nw">
                                <span class="badge {{ $cover < 5 ? 'badge-bad' : ($cover < 10 ? 'badge-warn' : 'badge-ok') }}">
                                    {{ $cover >= 99 ? '99+' : number_format($cover, 0) }} weeks
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @can('inventory.manage')
            <div class="row" style="gap:12px;align-items:flex-end;margin-top:16px">
                <div class="field">
                    <label class="label" for="reason">Reason for these corrections</label>
                    <select class="select" id="reason" name="reason" form="stockForm">
                        @foreach (['Cycle count', 'Damage write-off', 'Receipt not on the sheet', 'Customer return', 'Other'] as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Save and publish</button>
                <p class="hint" style="flex:1">Saving writes these figures to the storefront immediately and records what each one was before.</p>
            </div>
        </form>
    @endcan
</section>

{{-- -------------------------------------------------------- Transfer --- --}}
@can('inventory.manage')
    <section class="panel">
        <div class="panel-head">
            <h3>Move stock between warehouses</h3>
            <span class="small muted">Both legs are written together, so the total never drifts.</span>
        </div>

        <form method="POST" action="{{ route('admin.inventory.transfer') }}" class="inline-form">
            @csrf
            <div class="field" style="flex:2">
                <label class="label" for="t_product">Item</label>
                <select class="select" id="t_product" name="product_id" required>
                    @foreach ($rows->take(200) as $row)
                        <option value="{{ $row['product']->id }}">{{ $row['product']->sku }} — {{ $row['product']->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="t_from">From</label>
                <select class="select" id="t_from" name="from_warehouse_id" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="t_to">To</label>
                <select class="select" id="t_to" name="to_warehouse_id" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected($loop->last)>{{ $warehouse->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="t_qty">Quantity</label>
                <input class="input" id="t_qty" type="number" min="1" name="quantity" required>
            </div>
            <button class="btn btn-outline" type="submit">Transfer</button>
        </form>
    </section>
@endcan

@endsection
