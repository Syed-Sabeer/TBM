@extends('layouts.admin')

@section('title', $order->reference)
@section('heading', $order->reference)
@section('subheading', $order->company->name.' · placed '.$order->placed_at?->format('j F Y').' by '.($order->placedBy?->name ?? '—'))

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.pick-list', $order) }}" target="_blank">Pick list</a>
    <a class="btn btn-outline btn-sm" href="{{ route('account.orders.invoice', $order) }}" target="_blank">Invoice</a>
@endsection

@section('content')

{{-- ------------------------------------------------------ Next action --- --}}
@can('orders.manage')
    <section class="panel">
        <div class="panel-head">
            <h3>Status</h3>
            <x-status-badge :status="$order->status"/>
        </div>

        <div class="row" style="gap:10px;flex-wrap:wrap">
            @if ($order->status === \App\Enums\OrderStatus::PendingConfirmation)
                <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Confirm and allocate stock</button>
                </form>
                <p class="hint" style="flex:1">
                    Confirming reserves {{ number_format($order->total_pieces) }} pieces at
                    {{ $order->warehouse?->code }} — the storefront stops offering them immediately.
                </p>
            @elseif ($order->status === \App\Enums\OrderStatus::Confirmed)
                <form method="POST" action="{{ route('admin.orders.production', $order) }}">
                    @csrf
                    <button class="btn btn-outline" type="submit">Move to production</button>
                </form>
                <form method="POST" action="{{ route('admin.orders.ship', $order) }}" class="inline-form">
                    @csrf
                    <input class="input" name="shipping_service" value="{{ $order->shipping_service }}" placeholder="Carrier and service">
                    <button class="btn btn-primary" type="submit">Mark shipped</button>
                </form>
            @elseif ($order->status === \App\Enums\OrderStatus::InProduction)
                <form method="POST" action="{{ route('admin.orders.ship', $order) }}" class="inline-form">
                    @csrf
                    <input class="input" name="shipping_service" value="{{ $order->shipping_service }}" placeholder="Carrier and service">
                    <button class="btn btn-primary" type="submit">Mark shipped</button>
                </form>
            @elseif ($order->status === \App\Enums\OrderStatus::Shipped)
                <form method="POST" action="{{ route('admin.orders.deliver', $order) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Mark delivered</button>
                </form>
            @else
                <p class="hint">Nothing further to do on this order.</p>
            @endif

            @unless (in_array($order->status, [\App\Enums\OrderStatus::Shipped, \App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Cancelled], true))
                <div class="spacer"></div>
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="inline-form">
                    @csrf
                    <input class="input" name="reason" placeholder="Reason for cancelling" required>
                    <button class="btn btn-ghost" type="submit">Cancel order</button>
                </form>
            @endunless
        </div>
    </section>
@endcan

{{-- ----------------------------------------------------------- Lines --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>{{ $order->items->count() }} {{ Str::plural('line', $order->items->count()) }}</h3>
        @can('view-margin')
            <span class="small muted">Margin {{ number_format($order->marginPercent(), 1) }}% on merchandise</span>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr>
                    <th>Item</th><th>Description</th><th>Colour</th><th>Size</th><th>Decoration</th>
                    <th class="num">Qty</th><th class="num">Unit</th>
                    @can('view-margin')<th class="num">Cost</th><th class="num">Margin</th>@endcan
                    <th class="num">Line</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php
                        $margin = $item->unit_price > 0
                            ? (($item->unit_price - ($item->unit_cost ?? 0)) / $item->unit_price) * 100
                            : 0;
                    @endphp
                    <tr>
                        <td class="nw">
                            <span class="mono">{{ $item->sku }}</span>
                            {{-- Staff view: the mill reference under the item number. --}}
                            <div class="sku-parent">{{ $item->parent_sku }}</div>
                        </td>
                        <td><span class="trunc" style="max-width:180px">{{ $item->name }}</span></td>
                        <td>{{ $item->colour_name }}</td>
                        <td>{{ $item->size }}</td>
                        <td class="muted">{{ $item->decoration }}</td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">{{ \App\Support\Money::unit($item->unit_price) }}</td>
                        @can('view-margin')
                            <td class="num muted">{{ \App\Support\Money::unit($item->unit_cost) }}</td>
                            <td class="num">
                                <span class="badge {{ $margin < config('tbm.margin_floor') ? 'badge-warn' : 'badge-ok' }}">
                                    {{ number_format($margin, 1) }}%
                                </span>
                            </td>
                        @endcan
                        <td class="num"><b>{{ \App\Support\Money::format($item->line_total) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- ----------------------------------------------------------- Facts --- --}}
<div class="panel-pair">
    <section class="panel">
        <div class="panel-head"><h3>Account</h3></div>
        <dl class="fact-grid">
            <div><dt>Account</dt><dd><a class="lk" href="{{ route('admin.companies.show', $order->company) }}">{{ $order->company->name }}</a></dd></div>
            <div><dt>Number</dt><dd class="mono">{{ $order->company->account_number }}</dd></div>
            <div><dt>Rate card</dt><dd>{{ $order->company->tier?->fullName() }}</dd></div>
            <div><dt>Terms</dt><dd>{{ $order->payment_terms }}</dd></div>
            <div><dt>Placed by</dt><dd>{{ $order->placedBy?->name ?? '—' }}</dd></div>
            <div><dt>Their PO</dt><dd class="mono">{{ $order->customer_po ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Shipping</h3></div>
        <dl class="fact-grid">
            <div><dt>Ship to</dt><dd>{!! implode('<br>', array_map('e', $order->shipToLines())) !!}</dd></div>
            <div><dt>From</dt><dd>{{ $order->warehouse?->name }}</dd></div>
            <div><dt>Service</dt><dd>{{ $order->shipping_service ?? '—' }}</dd></div>
            <div><dt>In hands</dt><dd>{{ $order->in_hands_on?->format('j M Y') ?? '—' }}</dd></div>
            <div><dt>Merchandise</dt><dd>{{ \App\Support\Money::format($order->merchandise_total) }}</dd></div>
            <div><dt>Total</dt><dd>{{ \App\Support\Money::format($order->grand_total) }}</dd></div>
        </dl>

        @if ($order->customer_notes)
            <p class="hint"><b>Customer notes:</b> {{ $order->customer_notes }}</p>
        @endif
    </section>
</div>

{{-- ----------------------------------------------------------- Notes --- --}}
<section class="panel">
    <div class="panel-head"><h3>Notes</h3></div>

    <ul class="note-list">
        @forelse ($order->notes as $note)
            <li>
                <span class="note-meta">
                    {{ $note->authorName() }} · {{ $note->created_at->format('j M Y H:i') }}
                    @if ($note->is_internal)
                        <span class="badge">Internal</span>
                    @else
                        <span class="badge badge-ok">Customer sees this</span>
                    @endif
                </span>
                <p>{{ $note->body }}</p>
            </li>
        @empty
            <li class="muted">No notes yet.</li>
        @endforelse
    </ul>

    <form method="POST" action="{{ route('admin.orders.notes.store', $order) }}">
        @csrf
        <div class="field">
            <label class="label" for="body">Add a note</label>
            <textarea class="input" id="body" name="body" rows="2" required></textarea>
        </div>

        <div class="row" style="gap:16px;align-items:center">
            <label class="check">
                <input type="checkbox" name="is_internal" value="1" checked>
                <span>Internal only</span>
            </label>
            <span class="hint" style="flex:1">Uncheck and the customer sees it on their order page.</span>
            <button class="btn btn-outline btn-sm" type="submit">Add note</button>
        </div>
    </form>
</section>

@endsection
