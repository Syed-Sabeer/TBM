@extends('documents.layout')

@section('title', 'Packing slip '.$order->reference)

@section('content')

<header class="doc-head">
    <div>
        <h1 class="doc-title">Packing slip</h1>
        <span class="mono">{{ $order->reference }}</span>
    </div>
    <div class="doc-meta">
        <b>{{ config('tbm.company.legal_name') }}</b><br>
        Shipped from {{ $order->warehouse?->name }}<br>
        {{ $order->warehouse?->locationLine() }}
    </div>
</header>

<div class="doc-parties">
    <div>
        <h4>Ship to</h4>
        <p>{!! implode('<br>', array_map('e', $order->shipToLines())) !!}</p>
    </div>
    <div>
        <h4>Details</h4>
        <p>
            Shipped: {{ $order->shipped_at?->format('j F Y') ?? 'Pending' }}<br>
            Service: {{ $order->shipping_service ?? '—' }}<br>
            Your PO: <span class="mono">{{ $order->customer_po ?: '—' }}</span><br>
            Job: {{ $order->job_reference ?: '—' }}
        </p>
    </div>
</div>

<table>
    <thead>
        <tr><th>Item</th><th>Description</th><th>Colour</th><th>Size</th><th class="num">Qty</th></tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                {{--
                    Customer item number only. The picking team works from the
                    pick list, which is a separate document and carries the mill
                    reference; this one goes in the box.
                --}}
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
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="row-total"><td colspan="4" class="num">Total pieces</td><td class="num">{{ number_format($order->total_pieces) }}</td></tr>
    </tfoot>
</table>

<div class="doc-foot">
    <p>No prices appear on this document. Check the count on receipt and report any shortage within five business days, quoting {{ $order->reference }}.</p>
    @if ($order->customer_notes)
        <p><b>Notes on this order:</b> {{ $order->customer_notes }}</p>
    @endif
</div>

@endsection
