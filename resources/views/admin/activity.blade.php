@extends('layouts.admin')

@section('title', 'Activity')
@section('heading', 'Activity log')
@section('subheading', 'Who changed what, and when.')

@section('content')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Action or detail">
        </div>
        <div class="field">
            <label class="label" for="user">Who</label>
            <select class="select" id="user" name="user" onchange="this.form.submit()">
                <option value="">Anyone</option>
                @foreach ($people as $person)
                    <option value="{{ $person->id }}" @selected((string) ($filters['user'] ?? '') === (string) $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.activity') }}">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead><tr><th>When</th><th>Action</th><th>Detail</th><th>Who</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td class="nw muted">{{ $entry->created_at->format('j M H:i') }}</td>
                        <td class="nw"><b>{{ $entry->action }}</b></td>
                        <td>{{ $entry->detail }}</td>
                        <td class="muted">{{ $entry->actorName() }}</td>
                        <td class="mono muted small">{{ $entry->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Nothing logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}
</section>

@endsection
