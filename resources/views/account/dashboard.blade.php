@extends('layouts.account')

@section('title', 'Your account')
@section('heading', 'Hello, '.auth()->user()->firstName())
@section('subheading', $company->name.' · account '.$company->account_number)

@section('actions')
    <a class="btn btn-primary" href="{{ route('shop') }}">Start an order <x-icon name="arrow"/></a>
@endsection

@section('panel')

    {{-- ------------------------------------------------------- Headline --- --}}
    @pricing
        <div class="mini-kpi">
            <x-stat label="Spend, last 12 months"
                    :value="\App\Support\Money::compact($headline['value'])"
                    :delta="$headline['value_change']"
                    sub="vs the 12 before"/>

            <x-stat label="Pieces bought"
                    :value="\App\Support\Money::compactNumber($headline['pieces'])"
                    :delta="$headline['pieces_change']"
                    sub="vs the 12 before"/>

            <x-stat label="Orders placed"
                    :value="$headline['orders']"
                    :delta="$headline['orders_change']"
                    sub="vs the 12 before"/>

            <x-stat label="Average order"
                    :value="\App\Support\Money::format($headline['average_order'])"
                    sub="last 12 months"/>
        </div>
    @endpricing

    {{-- ---------------------------------------------------- Open orders --- --}}
    <section class="panel">
        <div class="panel-head">
            <h3>In flight</h3>
            <a class="link-arrow btn-sm" href="{{ route('account.orders.index') }}">All orders <x-icon name="arrow"/></a>
        </div>

        @if ($openOrders->isEmpty())
            <div class="empty">
                <x-icon name="check"/>
                <h3>Nothing outstanding</h3>
                <p>Every order on the account has been delivered.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Reference</th><th>Placed</th><th>Your PO</th><th class="num">Pieces</th><th class="num">Value</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($openOrders as $order)
                            <tr>
                                <td><a class="lk mono" href="{{ route('account.orders.show', $order) }}">{{ $order->reference }}</a></td>
                                <td class="nw">{{ $order->placed_at?->format('j M Y') }}</td>
                                <td class="mono muted">{{ $order->customer_po ?: '—' }}</td>
                                <td class="num">{{ number_format($order->total_pieces) }}</td>
                                <td class="num">@pricing {{ \App\Support\Money::format($order->merchandise_total) }} @else — @endpricing</td>
                                <td><x-status-badge :status="$order->status"/></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- -------------------------------------------------------- Charts --- --}}
    @pricing
        <div class="panel-pair">
            <section class="panel">
                <div class="panel-head">
                    <h3>What you have spent, month by month</h3>
                    <a class="link-arrow btn-sm" href="{{ route('account.reports') }}">Full reports</a>
                </div>
                <x-column-chart :rows="$byMonth" value-label="Spend" format="money"/>
            </section>

            <section class="panel">
                <div class="panel-head"><h3>Your top items</h3></div>
                <x-bar-list :rows="$topItems->map(fn ($r) => ['label' => $r['sku'], 'meta' => $r['name'], 'value' => $r['pieces']])"
                            format="number"/>
            </section>
        </div>
    @endpricing

    {{-- ------------------------------------------------ Recent activity --- --}}
    <section class="panel">
        <div class="panel-head">
            <h3>Recent orders across the account</h3>
            <span class="small muted">Everyone on {{ $company->name }} sees these, whoever placed them.</span>
        </div>

        <div class="table-wrap">
            <table class="table table-compact">
                <thead>
                    <tr><th>Reference</th><th>Placed by</th><th>Date</th><th class="num">Value</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a class="lk mono" href="{{ route('account.orders.show', $order) }}">{{ $order->reference }}</a></td>
                            <td>{{ $order->placedBy?->name ?? '—' }}</td>
                            <td class="nw muted">{{ $order->placed_at?->format('j M Y') }}</td>
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
                        <tr><td colspan="6" class="muted">No orders yet. <a href="{{ route('shop') }}">Browse the catalogue</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ----------------------------------------------------- Terms card --- --}}
    @pricing
        <section class="panel">
            <div class="panel-head"><h3>Your terms</h3></div>
            <dl class="fact-grid">
                <div><dt>Rate card</dt><dd>{{ $company->tier?->fullName() ?? 'Standard' }}</dd></div>
                <div><dt>Payment terms</dt><dd>{{ $company->payment_terms }}</dd></div>
                <div><dt>Credit limit</dt><dd>{{ \App\Support\Money::format($company->credit_limit) }}</dd></div>
                <div><dt>Available</dt><dd>{{ \App\Support\Money::format($company->creditAvailable()) }}</dd></div>
                <div><dt>Account manager</dt><dd>{{ $company->accountManager?->name ?? 'Unassigned' }}</dd></div>
                <div><dt>Customer since</dt><dd>{{ $company->customer_since?->format('F Y') }}</dd></div>
            </dl>

            @if ($company->credit_limit > 0)
                <div class="meter" role="img"
                     aria-label="{{ round($company->creditUsedPercent()) }}% of credit used">
                    <span style="width:{{ round($company->creditUsedPercent(), 1) }}%"></span>
                </div>
                <p class="hint">{{ \App\Support\Money::format($company->credit_used) }} of {{ \App\Support\Money::format($company->credit_limit) }} in use.</p>
            @endif
        </section>
    @endpricing

@endsection
