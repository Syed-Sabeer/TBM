@extends('layouts.admin')

@section('title', 'Accounts')
@section('heading', 'Customer accounts')
@section('subheading', 'Who buys from us, on what terms, at what rate.')

@section('content')

<section class="panel">
    <form method="GET" class="adm-toolbar">
        <div class="field" style="flex:1;min-width:220px">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Company name or account number">
        </div>

        <div class="field">
            <label class="label" for="status">Status</label>
            <select class="select" id="status" name="status" onchange="this.form.submit()">
                <option value="">Any</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="tier">Rate card</label>
            <select class="select" id="tier" name="tier" onchange="this.form.submit()">
                <option value="">Any tier</option>
                @foreach ($tiers as $tier)
                    <option value="{{ $tier->id }}" @selected((string) ($filters['tier'] ?? '') === (string) $tier->id)>{{ $tier->fullName() }}</option>
                @endforeach
            </select>
        </div>

        <div class="spacer"></div>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.companies.index') }}">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="table adm-table">
            <thead>
                <tr>
                    <th>Account</th><th>Number</th><th>Rate card</th><th>Manager</th>
                    <th class="num">Logins</th><th class="num">Spend</th><th>Terms</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td>
                            <a class="lk" href="{{ route('admin.companies.show', $company) }}"><b>{{ $company->name }}</b></a>
                            @if ($company->trading_name)
                                <div class="sku-parent">t/a {{ $company->trading_name }}</div>
                            @endif
                        </td>
                        <td class="mono muted">{{ $company->account_number }}</td>
                        <td>{{ $company->tier?->fullName() ?? '—' }}</td>
                        <td class="muted">{{ $company->accountManager?->name ?? 'Unassigned' }}</td>
                        <td class="num">{{ $company->users_count }}</td>
                        <td class="num">{{ \App\Support\Money::compact($company->spend ?? 0) }}</td>
                        <td class="nw muted">{{ $company->payment_terms }}</td>
                        <td><x-status-badge :status="$company->status"/></td>
                        <td class="num"><a class="btn btn-outline btn-sm" href="{{ route('admin.companies.show', $company) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No accounts match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $companies->links() }}
</section>

@endsection
