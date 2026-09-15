@extends('layouts.account')

@section('title', 'My price list')
@section('heading', 'Your price list')
@section('subheading', $company->tier?->fullName().' — every item, at every break, at your rates.')

@section('panel')

<div class="notice notice-info">
    <x-icon name="tag"/>
    <p>
        These are your numbers, not a list price. Rows marked <b>Your rate</b> carry a discount
        negotiated for {{ $company->name }} on top of your tier. Prices are FOB warehouse and exclude
        freight and decoration.
    </p>
</div>

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:200px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Item number or name">
        </div>
        <div class="field">
            <label class="label" for="category">Category</label>
            <select class="select" id="category" name="category" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <button class="btn btn-ghost btn-sm" type="button" onclick="window.print()">Print</button>
    </form>

    <div class="table-wrap">
        <table class="table table-compact rate-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    @foreach ($breaks as $break)
                        <th class="num">{{ number_format($break) }}+</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="mono nw">
                            <a class="lk" href="{{ route('product', $row['product']) }}">{{ $row['product']->sku }}</a>
                            @if ($row['negotiated'])
                                <span class="badge badge-ok">Your rate</span>
                            @endif
                        </td>
                        <td><span class="trunc" style="max-width:240px">{{ $row['product']->name }}</span></td>
                        @foreach ($row['ladder'] as $break)
                            <td class="num">{{ \App\Support\Money::unit($break->unitPrice) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($breaks) + 2 }}" class="muted">Nothing matches that search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<p class="hint">
    Need a rate reviewed? Your account manager is
    {{ $company->accountManager?->name ?? 'assigned on request' }} — {{ config('tbm.company.email') }}.
</p>

@endsection
