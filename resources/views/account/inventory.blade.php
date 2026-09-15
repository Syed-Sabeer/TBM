@extends('layouts.account')

@section('title', 'Stock watch')
@section('heading', 'Stock watch')
@section('subheading', $showingAll ? 'Everything we hold.' : 'The items you buy, and what is on the shelf right now.')

@section('actions')
    @if ($hasHistory)
        <a class="btn btn-outline btn-sm" href="{{ route('account.inventory', ['all' => $showingAll ? null : 1]) }}">
            {{ $showingAll ? 'Just my items' : 'Show everything' }}
        </a>
    @endif
@endsection

@section('panel')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:200px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Item number or name">
        </div>
        @if ($showingAll)
            <input type="hidden" name="all" value="1">
        @endif
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Search</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th><th>Description</th>
                    @foreach ($warehouses as $warehouse)
                        <th class="num" title="{{ $warehouse->name }}">{{ $warehouse->code }}</th>
                    @endforeach
                    <th class="num">Total</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $total = $product->totalStock(); @endphp
                    <tr>
                        <td class="mono nw"><a class="lk" href="{{ route('product', $product) }}">{{ $product->sku }}</a></td>
                        <td><span class="trunc" style="max-width:220px">{{ $product->name }}</span></td>
                        @foreach ($warehouses as $warehouse)
                            @php $at = $product->stockAt($warehouse); @endphp
                            <td class="num {{ $at === 0 ? 'muted' : '' }}">{{ $at > 0 ? number_format($at) : '—' }}</td>
                        @endforeach
                        <td class="num">
                            <b class="{{ $total > 0 ? '' : 'is-out' }}">{{ $total > 0 ? number_format($total) : 'Backorder' }}</b>
                        </td>
                        <td class="num">
                            <a class="btn btn-outline btn-sm" href="{{ route('product', $product) }}">Order</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $warehouses->count() + 4 }}" class="muted">Nothing to show yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<p class="hint">
    These are the same figures our picking team works from, refreshed each morning from the mill sheet.
    A zero means backorder, not "gone" — ask your rep for the intake date.
</p>

@endsection
