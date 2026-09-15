@extends('layouts.account')

@section('title', 'Order '.$order->reference)
@section('heading', 'Order '.$order->reference)
@section('subheading', 'Placed '.$order->placed_at?->format('j F Y').' by '.($order->placedBy?->name ?? 'your account'))

@section('actions')
    @can('downloadDocuments', $order)
        <a class="btn btn-outline btn-sm" href="{{ route('account.orders.invoice', $order) }}" target="_blank">Invoice</a>
        <a class="btn btn-outline btn-sm" href="{{ route('account.orders.packing-slip', $order) }}" target="_blank">Packing slip</a>
    @endcan
    @can('reorder', $order)
        <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
            @csrf
            <button class="btn btn-primary btn-sm" type="submit">Reorder these lines</button>
        </form>
    @endcan
@endsection

@section('panel')

    {{-- ------------------------------------------------------ Progress --- --}}
    @unless ($order->isCancelled())
        <section class="panel">
            <ol class="track">
                @foreach (['Received', 'Confirmed', 'In production', 'Shipped', 'Delivered'] as $i => $step)
                    @php $stepNo = $i + 1; @endphp
                    <li class="{{ $order->status->step() >= $stepNo ? 'is-done' : '' }} {{ $order->status->step() === $stepNo ? 'is-now' : '' }}">
                        <span class="track-dot">@if ($order->status->step() > $stepNo)<x-icon name="check"/>@endif</span>
                        <b>{{ $step }}</b>
                        <span class="track-when">
                            @switch($stepNo)
                                @case(1) {{ $order->placed_at?->format('j M') }} @break
                                @case(2) {{ $order->confirmed_at?->format('j M') }} @break
                                @case(4) {{ $order->shipped_at?->format('j M') }} @break
                                @case(5) {{ $order->delivered_at?->format('j M') }} @break
                            @endswitch
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    @else
        <div class="notice notice-warn">
            <x-icon name="x"/>
            <p>This order was cancelled. The lines are still here, and you can reorder them at today's prices.</p>
        </div>
    @endunless

    {{-- --------------------------------------------------------- Lines --- --}}
    <section class="panel">
        <div class="panel-head">
            <h3>{{ $order->items->count() }} {{ Str::plural('line', $order->items->count()) }}</h3>
            <span class="small muted">Prices as agreed when the order was placed.</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th><th>Description</th><th>Colour</th><th>Size</th><th>Decoration</th>
                        <th class="num">Qty</th><th class="num">Unit</th><th class="num">Line</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            {{-- The customer item number, the same one on the invoice. --}}
                            <td class="mono nw">
                                @if ($item->product)
                                    <a class="lk" href="{{ route('product', $item->product) }}">{{ $item->sku }}</a>
                                @else
                                    {{ $item->sku }}
                                @endif
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->colour_name }}</td>
                            <td>{{ $item->size }}</td>
                            <td class="muted">{{ $item->decoration }}</td>
                            <td class="num">{{ number_format($item->quantity) }}</td>
                            <td class="num">@pricing {{ \App\Support\Money::unit($item->unit_price) }} @else — @endpricing</td>
                            <td class="num">@pricing <b>{{ \App\Support\Money::format($item->line_total) }}</b> @else — @endpricing</td>
                        </tr>
                    @endforeach
                </tbody>
                @pricing
                    <tfoot>
                        <tr><th colspan="7" class="num">Merchandise</th><td class="num">{{ \App\Support\Money::format($order->merchandise_total) }}</td></tr>
                        @if ($order->decoration_total > 0)
                            <tr><th colspan="7" class="num">Decoration</th><td class="num">{{ \App\Support\Money::format($order->decoration_total) }}</td></tr>
                        @endif
                        <tr><th colspan="7" class="num">Freight</th><td class="num">{{ \App\Support\Money::format($order->freight_total) }}</td></tr>
                        <tr class="row-total"><th colspan="7" class="num">Total</th><td class="num">{{ \App\Support\Money::format($order->grand_total) }}</td></tr>
                    </tfoot>
                @endpricing
            </table>
        </div>
    </section>

    {{-- --------------------------------------------------------- Facts --- --}}
    <div class="panel-pair">
        <section class="panel">
            <div class="panel-head"><h3>Shipping</h3></div>
            <dl class="fact-grid">
                <div><dt>Ship to</dt><dd>{!! implode('<br>', array_map('e', $order->shipToLines())) !!}</dd></div>
                <div><dt>Ships from</dt><dd>{{ $order->warehouse?->name ?? '—' }}</dd></div>
                <div><dt>Service</dt><dd>{{ $order->shipping_service ?? '—' }}</dd></div>
                <div><dt>In hands by</dt><dd>{{ $order->in_hands_on?->format('j M Y') ?? 'Not specified' }}</dd></div>
            </dl>
        </section>

        <section class="panel">
            <div class="panel-head"><h3>References</h3></div>
            <dl class="fact-grid">
                <div><dt>Your PO</dt><dd class="mono">{{ $order->customer_po ?: '—' }}</dd></div>
                <div><dt>Job reference</dt><dd>{{ $order->job_reference ?: '—' }}</dd></div>
                <div><dt>Terms</dt><dd>{{ $order->payment_terms }}</dd></div>
                <div><dt>Placed by</dt><dd>{{ $order->placedBy?->name ?? '—' }}</dd></div>
            </dl>

            @if ($order->customer_notes)
                <p class="hint"><b>Your notes:</b> {{ $order->customer_notes }}</p>
            @endif
        </section>
    </div>

    {{-- --------------------------------------------------------- Notes --- --}}
    @if ($order->customerNotes->isNotEmpty())
        <section class="panel">
            <div class="panel-head"><h3>Updates from TBM</h3></div>
            <ul class="note-list">
                @foreach ($order->customerNotes as $note)
                    <li>
                        <span class="note-meta">{{ $note->authorName() }} · {{ $note->created_at->format('j M Y') }}</span>
                        <p>{{ $note->body }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ------------------------------------------------------- Withdraw --- --}}
    @can('cancel', $order)
        <section class="panel">
            <div class="panel-head"><h3>Withdraw this order</h3></div>
            <p class="hint">
                You can pull this back while it is still awaiting confirmation. Once a rep has confirmed it,
                stock is allocated and the warehouse may have started picking — give us a call instead.
            </p>

            <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="inline-form">
                @csrf
                <div class="field" style="flex:1">
                    <label class="label" for="reason">Reason</label>
                    <input class="input" id="reason" name="reason" required placeholder="Client changed the quantity">
                </div>
                <button class="btn btn-outline" type="submit">Withdraw order</button>
            </form>
        </section>
    @endcan

@endsection
