@extends('layouts.admin')

@section('title', $product->sku.' pricing')
@section('heading', $product->sku.' — pricing')
@section('subheading', $product->name)

@section('content')

<div class="mini-kpi">
    <x-stat label="Base price" :value="\App\Support\Money::unit($product->base_price)" sub="tier C at the smallest break"/>
    <x-stat label="FOB cost" :value="\App\Support\Money::unit($cost)" :sub="$product->cost_price ? 'from the mill sheet' : 'estimated'"/>
    <x-stat label="Margin floor" :value="number_format($floor, 0).'%'" sub="anything below needs sign-off"/>
    <x-stat label="Mill reference" :value="$product->parent_sku" sub="purchase orders only"/>
</div>

<section class="panel">
    <div class="panel-head">
        <h3>Every tier, every break</h3>
        <span class="small muted">Cells shaded amber fall below the margin floor.</span>
    </div>

    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr>
                    <th>Tier</th>
                    @foreach ($breaks as $break)
                        <th class="num">{{ number_format($break) }}+</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($tiers as $tier)
                    <tr>
                        <td class="nw">
                            <b>{{ $tier->fullName() }}</b>
                            <div class="sku-parent">{{ number_format($tier->discountPercent(), 1) }}% off standard</div>
                        </td>
                        @foreach ($matrix[$tier->code] as $break)
                            @php $margin = $break->unitPrice > 0 ? (($break->unitPrice - $cost) / $break->unitPrice) * 100 : 0; @endphp
                            <td class="num {{ $margin < $floor ? 'cell-warn' : '' }}">
                                {{ \App\Support\Money::unit($break->unitPrice) }}
                                <div class="sku-parent">{{ number_format($margin, 0) }}% margin</div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@if ($overrides->isNotEmpty())
    <section class="panel">
        <div class="panel-head">
            <h3>Accounts with a negotiated rate on this item</h3>
        </div>

        <div class="table-wrap">
            <table class="table table-compact adm-table">
                <thead><tr><th>Account</th><th>Tier</th><th>Concession</th><th>Note</th></tr></thead>
                <tbody>
                    @foreach ($overrides as $override)
                        <tr>
                            <td><a class="lk" href="{{ route('admin.companies.show', $override->company) }}">{{ $override->company->name }}</a></td>
                            <td>{{ $override->company->tier?->fullName() }}</td>
                            <td><span class="badge badge-ok">{{ $override->label() }}</span></td>
                            <td class="muted">{{ $override->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<p class="hint">
    The unit price is always base × quantity break × tier × any negotiated factor, in that order.
    Nothing in the application computes a price any other way.
</p>

@endsection
