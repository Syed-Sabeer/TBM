@extends('layouts.admin')

@section('title', 'Staff')
@section('heading', 'Staff logins')
@section('subheading', 'A role is a job title. The permissions behind it are defined once, not ticked per person.')

@section('toolbar')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.users.create') }}">Add a login</a>
@endsection

@section('content')

<section class="panel">
    <div class="table-wrap">
        <table class="table adm-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last seen</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="user-row">
                                <span class="avatar">{{ $user->initials() }}</span>
                                <span><b>{{ $user->name }}</b><span class="muted">{{ $user->job_title }}</span></span>
                            </div>
                        </td>
                        <td class="muted">{{ $user->email }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge">{{ Str::headline($role->name) }}</span>
                            @endforeach
                        </td>
                        <td class="muted nw">{{ $user->last_seen_at?->diffForHumans(short: true) ?? 'Never' }}</td>
                        <td><span class="badge {{ $user->is_active ? 'badge-ok' : 'badge-bad' }}">{{ $user->is_active ? 'Active' : 'Off' }}</span></td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.users.edit', $user) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <h3>What each role can do</h3>
        <span class="small muted">The Owner role holds everything, including anything added later.</span>
    </div>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead><tr><th>Role</th><th class="num">Permissions</th><th>Covers</th></tr></thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td><b>{{ Str::headline($role->name) }}</b></td>
                        <td class="num">{{ $role->permissions_count }}</td>
                        <td class="muted small">
                            {{ $role->permissions()->pluck('name')->map(fn ($p) => Str::headline(str_replace('.', ' ', $p)))->join(', ') ?: '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection
