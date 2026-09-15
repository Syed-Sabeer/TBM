@extends('layouts.admin')

@section('title', 'Overview')
@section('heading', 'Good morning')
@section('subheading', 'What needs a person today.')

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.imports.create') }}">Run an import</a>
@endsection

@section('content')

{{-- ---------------------------------------------------------- Headline --- --}}
<div class="mini-kpi">
    <x-stat label="Revenue, last 12 months"
            :value="\App\Support\Money::compact($headline['value'])"
            :delta="$headline['value_change']" sub="vs the 12 before"/>
    <x-stat label="Pieces shipped"
            :value="\App\Support\Money::compactNumber($headline['pieces'])"
            :delta="$headline['pieces_change']" sub="vs the 12 before"/>
    <x-stat label="Open orders" :value="$headline['open_orders']" sub="not yet delivered"/>
    <x-stat label="Units on hand"
            :value="\App\Support\Money::compactNumber($counts['units'])"
            :sub="$counts['skus'].' items live'"/>
</div>

{{-- ------------------------------------------------------- Needs action --- --}}
<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>Waiting on confirmation</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.orders.index', ['status' => 'pending_confirmation']) }}">All <x-icon name="arrow"/></a>
        </div>

        @if ($awaitingConfirmation->isEmpty())
            <div class="empty"><x-icon name="check"/><h3>Nothing waiting</h3><p>Every order has been picked up.</p></div>
        @else
            <div class="table-wrap">
                <table class="table table-compact adm-table">
                    <thead><tr><th>Reference</th><th>Account</th><th class="num">Value</th><th>Placed</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($awaitingConfirmation as $order)
                            <tr>
                                <td><a class="lk mono" href="{{ route('admin.orders.show', $order) }}">{{ $order->reference }}</a></td>
                                <td><span class="trunc" style="max-width:160px">{{ $order->company->name }}</span></td>
                                <td class="num">{{ \App\Support\Money::format($order->merchandise_total) }}</td>
                                <td class="nw muted">{{ $order->placed_at?->diffForHumans(short: true) }}</td>
                                <td class="num">
                                    @can('orders.manage')
                                        <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">
                                            @csrf
                                            <button class="btn btn-primary btn-sm" type="submit">Confirm</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>Applications to review</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.companies.index', ['status' => 'pending_approval']) }}">All <x-icon name="arrow"/></a>
        </div>

        @if ($pendingAccounts->isEmpty())
            <div class="empty"><x-icon name="check"/><h3>None outstanding</h3><p>No account is waiting on pricing.</p></div>
        @else
            <ul class="req-list">
                @foreach ($pendingAccounts as $company)
                    <li>
                        <div>
                            <a class="lk" href="{{ route('admin.companies.show', $company) }}"><b>{{ $company->name }}</b></a>
                            <span class="muted">{{ $company->business_type }} · {{ $company->users_count }} {{ Str::plural('login', $company->users_count) }} · applied {{ $company->created_at->diffForHumans(short: true) }}</span>
                        </div>
                        <a class="btn btn-outline btn-sm" href="{{ route('admin.companies.show', $company) }}">Review</a>
                    </li>
                @endforeach
            </ul>
            <p class="hint">Until one of these is approved, nobody on that account can see a single price.</p>
        @endif
    </section>
</div>

{{-- ----------------------------------------------------------- Import --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>Last import</h3>
        <a class="link-arrow btn-sm" href="{{ route('admin.imports.index') }}">History <x-icon name="arrow"/></a>
    </div>

    @if ($lastImport)
        <div class="imp-summary">
            <div>
                <span class="mono">{{ $lastImport->reference }}</span>
                <b>{{ $lastImport->filename }}</b>
                <span class="muted">{{ $lastImport->sourceLabel() }} · {{ $lastImport->created_at->diffForHumans() }}</span>
            </div>
            <div class="imp-figures">
                <span>{{ $lastImport->summaryLine() }}</span>
                <span class="badge {{ $lastImport->status->badgeClass() }}">{{ $lastImport->status->label() }}</span>
            </div>
            <a class="btn btn-outline btn-sm" href="{{ route('admin.imports.show', $lastImport) }}">Open</a>
        </div>

        @if ($importsWaiting > 0)
            <div class="notice notice-warn">
                <x-icon name="shield"/>
                <p>{{ $importsWaiting }} {{ Str::plural('run', $importsWaiting) }} previewed but not applied — the storefront is still showing yesterday's figures for those lines.</p>
            </div>
        @endif
    @else
        <div class="empty"><x-icon name="refresh"/><h3>No imports yet</h3><p>Upload the mill sheet to publish stock.</p>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.imports.create') }}">Run one</a></div>
    @endif
</section>

{{-- ----------------------------------------------------------- Charts --- --}}
<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>Revenue by month</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.reports.index') }}">Reports</a>
        </div>
        <x-column-chart :rows="$byMonth" value-label="Revenue" format="money"/>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>Biggest accounts</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.reports.customers') }}">All</a>
        </div>
        <x-bar-list :rows="$topCompanies->map(fn ($r) => ['label' => $r['name'], 'meta' => $r['orders'].' orders', 'value' => $r['value']])"
                    format="money"/>
    </section>
</div>

{{-- ------------------------------------------------------ Stock alerts --- --}}
<div class="panel-pair">
    <section class="panel">
        <div class="panel-head">
            <h3>Out somewhere</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.inventory.index', ['view' => 'out']) }}">Stock <x-icon name="arrow"/></a>
        </div>

        @if ($stockAlerts->isEmpty())
            <div class="empty"><x-icon name="check"/><h3>Everything covered</h3><p>No item is at zero in any warehouse.</p></div>
        @else
            <div class="table-wrap">
                <table class="table table-compact adm-table">
                    <thead><tr><th>Item</th><th>Description</th><th class="num">Total</th><th>Zero at</th></tr></thead>
                    <tbody>
                        @foreach ($stockAlerts as $product)
                            <tr>
                                <td class="nw">
                                    <span class="mono">{{ $product->sku }}</span>
                                    {{-- Staff see the mill reference, because a PO goes out against it. --}}
                                    <div class="sku-parent">{{ $product->parent_sku }}</div>
                                </td>
                                <td><span class="trunc" style="max-width:180px">{{ $product->name }}</span></td>
                                <td class="num">{{ number_format($product->totalStock()) }}</td>
                                <td>
                                    @foreach ($product->inventoryLevels->filter(fn ($l) => $l->available() === 0) as $level)
                                        <span class="badge badge-bad">{{ $level->warehouse->code }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>Moving fastest</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.reports.items') }}">All items</a>
        </div>
        <x-bar-list :rows="$topItems->map(fn ($r) => ['label' => $r['sku'], 'meta' => $r['name'], 'value' => $r['pieces']])"
                    format="number"/>
    </section>
</div>

{{-- --------------------------------------------------------- Activity --- --}}
@can('activity.view')
    <section class="panel">
        <div class="panel-head">
            <h3>Recent activity</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.activity') }}">Full log <x-icon name="arrow"/></a>
        </div>

        <ul class="act-list">
            @foreach ($activity as $entry)
                <li>
                    <span class="act-when">{{ $entry->created_at->diffForHumans(short: true) }}</span>
                    <b>{{ $entry->action }}</b>
                    <span class="act-detail">{{ $entry->detail }}</span>
                    <span class="act-who">{{ $entry->actorName() }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endcan

@endsection
