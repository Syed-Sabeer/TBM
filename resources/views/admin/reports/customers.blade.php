@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Revenue by account')
@section('subheading', 'The last '.$months.' months.')

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.reports.export', ['view' => 'customers', 'months' => $months]) }}">Export CSV</a>
@endsection

@section('content')

<section class="panel">
    <div class="table-wrap">
        <table class="table adm-table">
            <thead><tr><th class="num">#</th><th>Account</th><th>Number</th><th class="num">Orders</th><th class="num">Pieces</th><th class="num">Revenue</th><th class="num">Average order</th></tr></thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="num muted">{{ $i + 1 }}</td>
                        <td><a class="lk" href="{{ route('admin.companies.show', \App\Models\Company::find($row['company_id'])) }}">{{ $row['name'] }}</a></td>
                        <td class="mono muted">{{ $row['account_number'] }}</td>
                        <td class="num">{{ number_format($row['orders']) }}</td>
                        <td class="num">{{ number_format($row['pieces']) }}</td>
                        <td class="num"><b>{{ \App\Support\Money::format($row['value']) }}</b></td>
                        <td class="num muted">{{ \App\Support\Money::format($row['orders'] > 0 ? $row['value'] / $row['orders'] : 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection
