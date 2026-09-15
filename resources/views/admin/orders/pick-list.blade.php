@extends('documents.layout')

@section('title', 'Pick list '.$order->reference)

@section('content')

<header class="doc-head">
    <div>
        <h1 class="doc-title">Pick list</h1>
        <span class="mono">{{ $order->reference }}</span>
    </div>
    <div class="doc-meta">
        <b>{{ $order->warehouse?->name }}</b><br>
        {{ $order->warehouse?->locationLine() }}<br>
        Printed {{ now()->format('j M Y H:i') }}
    </div>
</header>

<div class="doc-parties">
    <div>
        <h4>For</h4>
        <p><b>{{ $order->company->name }}</b><br>{{ $order->company->account_number }}</p>
    </div>
    <div>
        <h4>Ship to</h4>
        <p>{!! implode('<br>', array_map('e', $order->shipToLines())) !!}</p>
    </div>
</div>

{{--
    The one document that leads with the mill reference, because that is what
    is printed on the cartons on the rack. The customer item number is shown
    alongside so the packer can check it against the packing slip that goes in
    the box — and this sheet does not.
--}}
<table>
    <thead>
        <tr>
            <th>Mill reference</th><th>Item number</th><th>Description</th>
            <th>Colour</th><th>Size</th><th class="num">Qty</th><th class="num">Picked</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td class="mono"><b>{{ $item->parent_sku }}</b></td>
                <td class="mono">{{ $item->sku }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->colour_name }}</td>
                <td>{{ $item->size }}</td>
                <td class="num">{{ number_format($item->quantity) }}</td>
                <td class="num">________</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="row-total"><td colspan="5" class="num">Total pieces</td><td class="num">{{ number_format($order->total_pieces) }}</td><td></td></tr>
    </tfoot>
</table>

<div class="doc-foot">
    <p><b>Internal document.</b> Mill references appear here and on purchase orders only — never on anything that reaches the customer.</p>
    @if ($order->customer_notes)
        <p><b>Customer notes:</b> {{ $order->customer_notes }}</p>
    @endif
    <p>Picked by ______________________ &nbsp;&nbsp; Checked by ______________________ &nbsp;&nbsp; Date ____________</p>
</div>

@endsection
