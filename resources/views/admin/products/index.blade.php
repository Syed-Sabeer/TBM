@extends('layouts.admin')

@section('title', 'Catalogue')
@section('heading', 'Catalogue')
@section('subheading', 'Both identifiers in one place: the mill reference we buy against, the item number the customer sees.')

@section('toolbar')
    @can('catalogue.manage')
        <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create') }}">Add an item</a>
    @endcan
    <a class="btn btn-outline btn-sm" href="{{ route('admin.products.export') }}">Export CSV</a>
@endsection

@section('content')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Item number, mill reference or name">
        </div>
        <div class="field">
            <label class="label" for="category">Category</label>
            <select class="select" id="category" name="category" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="label" for="state">State</label>
            <select class="select" id="state" name="state" onchange="this.form.submit()">
                <option value="">Any</option>
                <option value="live" @selected(($filters['state'] ?? '') === 'live')>Live</option>
                <option value="draft" @selected(($filters['state'] ?? '') === 'draft')>Not published</option>
            </select>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr><th>Item number</th><th>Mill reference</th><th>Name</th><th>Category</th><th class="num">Base</th><th class="num">MOQ</th><th class="num">Stock</th><th>State</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="mono"><b>{{ $product->sku }}</b></td>
                        <td class="mono muted">{{ $product->parent_sku }}</td>
                        <td><span class="trunc" style="max-width:220px">{{ $product->name }}</span></td>
                        <td class="muted">{{ $product->category->name }}</td>
                        <td class="num">{{ \App\Support\Money::unit($product->base_price) }}</td>
                        <td class="num muted">{{ number_format($product->moq) }}</td>
                        <td class="num">{{ number_format($product->inventoryLevels->sum('on_hand')) }}</td>
                        <td>
                            <span class="badge {{ $product->is_published ? 'badge-ok' : '' }}">
                                {{ $product->is_published ? 'Live' : 'Draft' }}
                            </span>
                        </td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $product) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">Nothing matches.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</section>

@endsection
