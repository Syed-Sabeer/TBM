@extends('layouts.admin')

@section('title', 'Map columns')
@section('heading', 'Map the columns')
@section('subheading', $import->filename.' · '.$import->type->label())

@section('content')

<section class="panel">
    <div class="panel-head">
        <h3>Which column is which</h3>
        <span class="small muted">We have guessed from the headers. Check them.</span>
    </div>

    <form method="POST" action="{{ route('admin.imports.preview', $import) }}">
        @csrf

        @foreach ($fields as $key => $field)
            <div class="maprow">
                <div class="maprow-field">
                    <b>{{ $field['label'] }}</b>
                    @if ($field['required'] ?? false)
                        <span class="badge badge-warn">Required</span>
                    @endif
                    <span class="muted">{{ $field['note'] }}</span>
                </div>

                <select class="select" name="map[{{ $key }}]">
                    <option value="">— not in this file —</option>
                    @foreach ($headers as $index => $header)
                        <option value="{{ $index }}" @selected(($suggested[$key] ?? null) === $index)>
                            {{ $header ?: 'Column '.($index + 1) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div class="form-actions">
            <button class="btn btn-primary btn-lg" type="submit">Preview the changes <x-icon name="arrow"/></button>
            <a class="btn btn-ghost" href="{{ route('admin.imports.index') }}">Cancel</a>
        </div>
    </form>
</section>

<div class="notice notice-info">
    <x-icon name="lock"/>
    <p>
        The mill reference is how each line finds its item. It stays on our side of the wall — the
        customer only ever sees the item number it maps to.
    </p>
</div>

@endsection
