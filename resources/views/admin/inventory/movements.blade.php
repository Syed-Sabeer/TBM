@extends('layouts.admin')

@section('title', 'Movement log')
@section('heading', 'Stock movements')
@section('subheading', 'Every change to a stock figure, what it was before, and who made it.')

@section('content')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:200px">
            <label class="label" for="sku">Item</label>
            <input class="input" id="sku" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="Item number or mill reference">
        </div>
        <div class="field">
            <label class="label" for="reason">Reason</label>
            <select class="select" id="reason" name="reason" onchange="this.form.submit()">
                <option value="">Any</option>
                @foreach (['import' => 'Morning import', 'cycle_count' => 'Cycle count', 'damage' => 'Damage write-off', 'transfer' => 'Transfer', 'order' => 'Despatch', 'return' => 'Return', 'receipt' => 'Receipt'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['reason'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.inventory.movements') }}">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead>
                <tr><th>When</th><th>Item</th><th>Warehouse</th><th class="num">Was</th><th class="num">Now</th><th class="num">Change</th><th>Reason</th><th>Who</th><th>Note</th></tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td class="nw muted">{{ $movement->created_at->format('j M H:i') }}</td>
                        <td class="nw">
                            <span class="mono">{{ $movement->product?->sku }}</span>
                            <div class="sku-parent">{{ $movement->product?->parent_sku }}</div>
                        </td>
                        <td class="mono">{{ $movement->warehouse?->code }}</td>
                        <td class="num muted">{{ number_format($movement->quantity_before) }}</td>
                        <td class="num"><b>{{ number_format($movement->quantity_after) }}</b></td>
                        <td class="num {{ $movement->isIncrease() ? 'diff-up' : 'diff-down' }}">{{ $movement->signedDelta() }}</td>
                        <td class="nw">{{ $movement->reasonLabel() }}</td>
                        <td class="muted">{{ $movement->user?->name ?? 'System' }}</td>
                        <td class="muted"><span class="trunc" style="max-width:200px">{{ $movement->note }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No movements match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movements->links() }}
</section>

@endsection
