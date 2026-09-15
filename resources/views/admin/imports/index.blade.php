@extends('layouts.admin')

@section('title', 'Imports')
@section('heading', 'Imports')
@section('subheading', 'The morning sheet, and everything else we load.')

@section('toolbar')
    @can('imports.run')
        <a class="btn btn-primary btn-sm" href="{{ route('admin.imports.create') }}">Run an import</a>
    @endcan
@endsection

@section('content')

<section class="panel">
    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr><th>Reference</th><th>File</th><th>Type</th><th>Ran</th><th>By</th><th>Result</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($imports as $import)
                    <tr>
                        <td class="mono nw"><a class="lk" href="{{ route('admin.imports.show', $import) }}">{{ $import->reference }}</a></td>
                        <td><span class="trunc" style="max-width:200px">{{ $import->filename }}</span></td>
                        <td class="nw">{{ $import->type->label() }}</td>
                        <td class="nw muted">{{ $import->created_at->format('j M H:i') }}</td>
                        <td class="muted">{{ $import->sourceLabel() }}</td>
                        <td class="muted small">{{ $import->summaryLine() }}</td>
                        <td>
                            <span class="badge {{ $import->status->badgeClass() }}">{{ $import->status->label() }}</span>
                            @if ($import->warnings > 0)
                                <span class="badge badge-warn">{{ $import->warnings }} to review</span>
                            @endif
                        </td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.imports.show', $import) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">Nothing imported yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $imports->links() }}
</section>

<p class="hint">
    Imports are staged, previewed and only then applied. Nothing touches a live stock figure until
    somebody with the apply permission says so.
</p>

@endsection
