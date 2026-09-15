@extends('documents.layout')

@section('title', 'Invoice '.$order->reference)

@section('content')

<header class="doc-head">
    <div>
        <h1 class="doc-title">Invoice</h1>
        <span class="mono">{{ $order->reference }}</span>
    </div>
    <div class="doc-meta">
        <b>{{ config('tbm.company.legal_name') }}</b><br>
        {!! nl2br(e(config('tbm.company.address'))) !!}<br>
        {{ config('tbm.company.phone') }}<br>
        {{ config('tbm.company.accounts_email') }}
    </div>
</header>

<div class="doc-parties">
    <div>
        <h4>Bill to</h4>
        <p>
            <b>{{ $order->company->name }}</b><br>
            {!! implode('<br>', array_map('e', $order->company->billingAddressLines())) !!}
        </p>
    </div>
    <div>
        <h4>Ship to</h4>
        <p>{!! implode('<br>', array_map('e', $order->shipToLines())) !!}</p>
    </div>
    <div>
        <h4>Details</h4>
        <p>
            Invoice date: {{ ($order->shipped_at ?? $order->placed_at)?->format('j F Y') }}<br>
            Order date: {{ $order->placed_at?->format('j F Y') }}<br>
            Terms: {{ $order->payment_terms }}<br>
            Account: <span class="mono">{{ $order->company->account_number }}</span>
        </p>
    </div>
    <div>
        <h4>Your references</h4>
        <p>
            PO: <span class="mono">{{ $order->customer_po ?: '—' }}</span><br>
            Job: {{ $order->job_reference ?: '—' }}<br>
            Ordered by: {{ $order->placedBy?->name ?? '—' }}
        </p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Item</th><th>Description</th><th>Colour</th><th>Size</th>
            <th class="num">Qty</th><th class="num">Unit</th><th class="num">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                {{-- The customer's item number. The mill reference is deliberately absent. --}}
                <td class="mono">{{ $item->sku }}</td>
                <td>
                    {{ $item->name }}
                    @if ($item->isDecorated())
                        <br><small>{{ $item->decoration }}</small>
                    @endif
                </td>
                <td>{{ $item->colour_name }}</td>
                <td>{{ $item->size }}</td>
                <td class="num">{{ number_format($item->quantity) }}</td>
                <td class="num">{{ \App\Support\Money::unit($item->unit_price) }}</td>
                <td class="num">{{ \App\Support\Money::format($item->line_total) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="6" class="num">Merchandise</td><td class="num">{{ \App\Support\Money::format($order->merchandise_total) }}</td></tr>
        @if ($order->decoration_total > 0)
            <tr><td colspan="6" class="num">Decoration</td><td class="num">{{ \App\Support\Money::format($order->decoration_total) }}</td></tr>
        @endif
        <tr><td colspan="6" class="num">Freight</td><td class="num">{{ \App\Support\Money::format($order->freight_total) }}</td></tr>
        <tr><td colspan="6" class="num">Tax</td><td class="num">{{ \App\Support\Money::format($order->tax_total) }}</td></tr>
        <tr class="row-total"><td colspan="6" class="num">Total due</td><td class="num">{{ \App\Support\Money::format($order->grand_total) }}</td></tr>
    </tfoot>
</table>

<div class="doc-foot">
    <p>
        Payable per terms above. Remittance to {{ config('tbm.company.accounts_email') }}, quoting
        {{ $order->reference }}. Goods remain our property until paid in full.
    </p>
    <p>Sales tax is charged unless a current resale certificate is on file for this account.</p>
</div>

@endsection
