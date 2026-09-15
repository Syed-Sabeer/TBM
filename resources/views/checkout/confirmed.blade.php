@extends('layouts.storefront')

@section('title', 'Order '.$order->reference.' received')

@section('content')
<div class="container confirm-shell">

    <div class="confirm-card">
        <span class="confirm-tick"><x-icon name="check"/></span>

        <h1>Order {{ $order->reference }} is in</h1>
        <p class="lede">
            A rep is checking stock and freight now. You will get an acknowledgement with confirmed dates —
            usually within a couple of hours on a business day.
        </p>

        <dl class="confirm-facts">
            <div><dt>Reference</dt><dd class="mono">{{ $order->reference }}</dd></div>
            @if ($order->customer_po)
                <div><dt>Your PO</dt><dd class="mono">{{ $order->customer_po }}</dd></div>
            @endif
            <div><dt>Pieces</dt><dd>{{ number_format($order->total_pieces) }}</dd></div>
            <div><dt>Merchandise</dt><dd>{{ \App\Support\Money::format($order->merchandise_total) }}</dd></div>
            <div><dt>Ships from</dt><dd>{{ $order->warehouse?->name }}</dd></div>
            <div><dt>Terms</dt><dd>{{ $order->payment_terms }}</dd></div>
        </dl>

        <table class="table table-compact">
            <thead>
                <tr><th>Item</th><th>Colour</th><th>Size</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Line</th></tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        {{-- The customer's item number, as it will appear on the packing slip and invoice. --}}
                        <td class="mono">{{ $item->sku }}</td>
                        <td>{{ $item->colour_name }}</td>
                        <td>{{ $item->size }}</td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">{{ \App\Support\Money::unit($item->unit_price) }}</td>
                        <td class="num">{{ \App\Support\Money::format($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="confirm-actions">
            <a class="btn btn-primary" href="{{ route('account.orders.show', $order) }}">Track this order</a>
            <a class="btn btn-outline" href="{{ route('shop') }}">Keep shopping</a>
        </div>
    </div>
</div>
@endsection
