@extends('layouts.account')

@section('title', 'Order history')
@section('heading', 'Order history')
@section('subheading', 'Every order on '.auth()->user()->company->name.', whoever placed it.')

@section('panel')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:200px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Reference, your PO, job name or item number">
        </div>

        <div class="field">
            <label class="label" for="status">Status</label>
            <select class="select" id="status" name="status" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="user">Placed by</label>
            <select class="select" id="user" name="user" onchange="this.form.submit()">
                <option value="">Anyone on the account</option>
                @foreach ($colleagues as $colleague)
                    <option value="{{ $colleague->id }}" @selected((string) ($filters['user'] ?? '') === (string) $colleague->id)>
                        {{ $colleague->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('account.orders.index') }}">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th><th>Date</th><th>Placed by</th><th>Your PO</th>
                    <th class="num">Pieces</th><th class="num">Value</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a class="lk mono" href="{{ route('account.orders.show', $order) }}">{{ $order->reference }}</a></td>
                        <td class="nw">{{ $order->placed_at?->format('j M Y') }}</td>
                        <td>{{ $order->placedBy?->name ?? '—' }}</td>
                        <td class="mono muted">{{ $order->customer_po ?: '—' }}</td>
                        <td class="num">{{ number_format($order->total_pieces) }}</td>
                        <td class="num">@pricing {{ \App\Support\Money::format($order->merchandise_total) }} @else — @endpricing</td>
                        <td><x-status-badge :status="$order->status"/></td>
                        <td class="num">
                            @can('reorder', $order)
                                <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
                                    @csrf
                                    <button class="btn btn-outline btn-sm" type="submit">Reorder</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No orders match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</section>

@endsection
