@extends('layouts.admin')

@section('title', $import->reference)
@section('heading', $import->reference)
@section('subheading', $import->filename.' · '.$import->type->label().' · '.$import->sourceLabel())

@section('toolbar')
    <span class="badge {{ $import->status->badgeClass() }}">{{ $import->status->label() }}</span>
@endsection

@section('content')

<div class="mini-kpi">
    <x-stat label="Lines read" :value="number_format($import->rows_read)"/>
    <x-stat label="Ready to apply" :value="number_format($counts['ready'] ?? 0)"/>
    <x-stat label="Need a decision" :value="number_format($import->rowsNeedingAttention())" sub="no match, or a new colour"/>
    <x-stat label="Applied" :value="number_format($import->rows_updated)" :sub="$import->applied_at?->format('j M H:i')"/>
</div>

{{-- The action bar. Applying is the only step that writes a live figure. --}}
<section class="panel">
    <div class="panel-head">
        <h3>What happens if you apply this</h3>
    </div>

    @if ($import->isApplied())
        <p class="hint">
            Applied {{ $import->applied_at?->diffForHumans() }} — {{ number_format($import->rows_updated) }} stock
            figures were written, each one recording what it replaced.
        </p>

        @can('rollBack', $import)
            <form method="POST" action="{{ route('admin.imports.rollback', $import) }}">
                @csrf
                <button class="btn btn-outline" type="submit">Roll this run back</button>
                <span class="hint">Every figure this run touched goes back to what it was before.</span>
            </form>
        @else
            <p class="hint">This run can no longer be rolled back — a later import has since touched these lines.</p>
        @endcan
    @else
        <p class="hint">
            {{ number_format($counts['ready'] ?? 0) }} lines will overwrite the current quantity for their item
            and warehouse, and the new figures appear on the storefront immediately. Lines that matched nothing
            are skipped, not guessed at.
        </p>

        @can('apply', $import)
            <form method="POST" action="{{ route('admin.imports.apply', $import) }}">
                @csrf
                <button class="btn btn-primary btn-lg" type="submit">
                    Apply {{ number_format($counts['ready'] ?? 0) }} lines
                </button>
            </form>
        @else
            <p class="hint">You do not have permission to apply an import. Someone with Quality &amp; Inventory access can.</p>
        @endcan
    @endif
</section>

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field">
            <label class="label" for="status">Show</label>
            <select class="select" id="status" name="status" onchange="this.form.submit()">
                <option value="">Every line</option>
                @foreach (\App\Enums\ImportRowStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(($filters['status'] ?? '') === $case->value)>
                        {{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="spacer"></div>
    </form>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead>
                <tr>
                    <th class="num">Line</th><th>Mill reference</th><th>Maps to</th><th>Description</th>
                    <th>Colour</th><th>WH</th><th class="num">Was</th><th class="num">Becomes</th>
                    <th class="num">Change</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $delta = $row->delta(); @endphp
                    <tr class="{{ $row->status->needsAttention() ? 'row-flag' : '' }}">
                        <td class="num muted">{{ $row->line_number }}</td>
                        <td class="mono">{{ $row->parent_sku ?: '—' }}</td>
                        <td class="mono">{{ $row->resolvedSku() ?: '—' }}</td>
                        <td><span class="trunc" style="max-width:180px">{{ $row->description }}</span></td>
                        <td>{{ $row->colourway?->name ?? $row->shade ?? '—' }}</td>
                        <td class="mono">{{ $row->warehouse?->code ?? $row->warehouse_code ?? '—' }}</td>
                        <td class="num muted">{{ $row->quantity_before !== null ? number_format($row->quantity_before) : '—' }}</td>
                        <td class="num"><b>{{ $row->quantity !== null ? number_format($row->quantity) : '—' }}</b></td>
                        <td class="num {{ $delta > 0 ? 'diff-up' : ($delta < 0 ? 'diff-down' : '') }}">
                            {{ $delta === null ? '—' : ($delta > 0 ? '+' : '').number_format($delta) }}
                        </td>
                        <td>
                            <span class="badge {{ $row->status->needsAttention() ? 'badge-warn' : ($row->isReady() ? 'badge-ok' : '') }}">
                                {{ $row->status->label() }}
                            </span>
                            @if ($row->message)
                                <div class="sku-parent">{{ $row->message }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
</section>

@endsection
