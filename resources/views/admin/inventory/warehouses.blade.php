@extends('layouts.admin')

@section('title', 'Warehouses')
@section('heading', 'Warehouses')
@section('subheading', 'Where the stock sits. A new site becomes a stock column and a ship-from option immediately.')

@section('toolbar')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.warehouses.create') }}">Add a warehouse</a>
@endsection

@section('content')

<section class="panel">
    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr><th>Code</th><th>Name</th><th>Location</th><th>Type</th><th class="num">Items</th><th class="num">Units</th><th>Lead time</th><th>On storefront</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($warehouses as $warehouse)
                    <tr>
                        <td class="mono"><b>{{ $warehouse->code }}</b></td>
                        <td>
                            {{ $warehouse->name }}
                            @unless ($warehouse->is_active)
                                <span class="badge badge-bad">Inactive</span>
                            @endunless
                        </td>
                        <td class="muted">{{ $warehouse->locationLine() }}</td>
                        <td>
                            {{ $warehouse->typeLabel() }}
                            @if ($warehouse->isConsignment())
                                <div class="sku-parent">for {{ $warehouse->company?->name }}</div>
                            @endif
                        </td>
                        <td class="num muted">{{ number_format($warehouse->inventory_levels_count) }}</td>
                        <td class="num">{{ number_format($warehouse->unitsOnHand()) }}</td>
                        <td class="nw muted">{{ $warehouse->lead_time ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $warehouse->include_in_storefront ? 'badge-ok' : '' }}">
                                {{ $warehouse->include_in_storefront ? 'Visible' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.warehouses.edit', $warehouse) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<p class="hint">
    A consignment site holds one account's goods, so it is never offered to anyone else as a ship-from
    and never appears on the public stock strip.
</p>

@endsection
