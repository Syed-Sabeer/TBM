@extends('layouts.admin')

@section('title', 'Rate cards')
@section('heading', 'Rate cards')
@section('subheading', 'The tiers every account sits on, and the concessions on top of them.')

@section('content')

{{-- ------------------------------------------------------------ Tiers --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>Tiers</h3>
        <span class="small muted">A tier factor multiplies every base price. Changing one reprices the catalogue for every account on it.</span>
    </div>

    <div class="tier-grid">
        @foreach ($tiers as $tier)
            <form class="tier-card" method="POST" action="{{ route('admin.pricing.tiers.update', $tier) }}">
                @csrf
                @method('PATCH')

                <div class="tier-code">Tier {{ $tier->code }}</div>

                <div class="field">
                    <label class="label" for="name{{ $tier->id }}">Name</label>
                    <input class="input" id="name{{ $tier->id }}" name="name" value="{{ $tier->name }}"
                           @cannot('pricing.manage') disabled @endcannot>
                </div>

                <div class="field">
                    <label class="label" for="factor{{ $tier->id }}">Factor</label>
                    <input class="input mono" id="factor{{ $tier->id }}" type="number" step="0.01" min="0.4" max="1.5"
                           name="factor" value="{{ $tier->factor }}"
                           @cannot('pricing.manage') disabled @endcannot>
                    <p class="hint">{{ number_format($tier->discountPercent(), 1) }}% off the standard card.</p>
                </div>

                <p class="tier-count">{{ $tier->companies_count }} {{ Str::plural('account', $tier->companies_count) }}</p>

                @can('pricing.manage')
                    <button class="btn btn-outline btn-sm btn-block" type="submit">Save</button>
                @endcan
            </form>
        @endforeach
    </div>
</section>

{{-- ----------------------------------------------------------- Matrix --- --}}
<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Item number, mill reference or name">
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Search</button>
    </form>

    <div class="panel-head">
        <h3>What each tier pays</h3>
        <span class="small muted">Unit price at the smallest break. Open an item for the full matrix and its margin.</span>
    </div>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead>
                <tr>
                    <th>Item</th><th>Description</th><th class="num">Base</th>
                    @foreach ($tiers as $tier)
                        <th class="num">Tier {{ $tier->code }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td class="nw">
                            <span class="mono">{{ $product->sku }}</span>
                            <div class="sku-parent">{{ $product->parent_sku }}</div>
                        </td>
                        <td><span class="trunc" style="max-width:200px">{{ $product->name }}</span></td>
                        <td class="num muted">{{ \App\Support\Money::unit($product->base_price) }}</td>
                        @foreach ($tiers as $tier)
                            <td class="num">{{ \App\Support\Money::unit($matrix[$product->id][$tier->code][0]->unitPrice ?? 0) }}</td>
                        @endforeach
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.pricing.matrix', $product) }}">Matrix</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</section>

{{-- -------------------------------------------------------- Overrides --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>Recent negotiated rates</h3>
        <span class="small muted">Set on an account page. Stored as a factor, so a tier change still flows through.</span>
    </div>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead><tr><th>Account</th><th>Item</th><th>Concession</th><th>Note</th><th>Set</th></tr></thead>
            <tbody>
                @forelse ($overrides as $override)
                    <tr>
                        <td><a class="lk" href="{{ route('admin.companies.show', $override->company) }}">{{ $override->company->name }}</a></td>
                        <td class="mono">{{ $override->product->sku }}</td>
                        <td><span class="badge badge-ok">{{ $override->label() }}</span></td>
                        <td class="muted"><span class="trunc" style="max-width:200px">{{ $override->note }}</span></td>
                        <td class="nw muted">{{ $override->created_at->format('j M y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No negotiated rates yet — every account is on straight tier pricing.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
