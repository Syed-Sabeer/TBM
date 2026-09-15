@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')
@section('subheading', 'Every order across every account.')

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.export', request()->query()) }}">Export CSV</a>
@endsection

@section('content')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Reference, PO, account, item number or mill reference">
        </div>

        <div class="field">
            <label class="label" for="status">Status</label>
            <select class="select" id="status" name="status" onchange="this.form.submit()">
                <option value="">Any</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="company">Account</label>
            <select class="select" id="company" name="company" onchange="this.form.submit()">
                <option value="">Any account</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) ($filters['company'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="warehouse">Ships from</label>
            <select class="select" id="warehouse" name="warehouse" onchange="this.form.submit()">
                <option value="">Any warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) ($filters['warehouse'] ?? '') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.orders.index') }}">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr>
                    <th>Reference</th><th>Date</th><th>Account</th><th>PO</th>
                    <th class="num">Pieces</th><th class="num">Value</th><th>From</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="nw"><a class="lk mono" href="{{ route('admin.orders.show', $order) }}">{{ $order->reference }}</a></td>
                        <td class="nw muted">{{ $order->placed_at?->format('j M y') }}</td>
                        <td>
                            <a class="lk" href="{{ route('admin.companies.show', $order->company) }}">
                                <span class="trunc" style="max-width:180px">{{ $order->company->name }}</span>
                            </a>
                            <div class="sku-parent">{{ $order->placedBy?->name }}</div>
                        </td>
                        <td class="mono muted">{{ $order->customer_po ?: '—' }}</td>
                        <td class="num">{{ number_format($order->total_pieces) }}</td>
                        <td class="num">{{ \App\Support\Money::format($order->merchandise_total) }}</td>
                        <td class="mono">{{ $order->warehouse?->code ?? '—' }}</td>
                        <td><x-status-badge :status="$order->status"/></td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $order) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No orders match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</section>

@endsection
