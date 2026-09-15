@extends('layouts.admin')

@section('title', 'Items')
@section('heading', 'What sells')
@section('subheading', 'The last '.$months.' months, by customer item number.')

@section('toolbar')
    <a class="btn btn-outline btn-sm" href="{{ route('admin.reports.export', ['view' => 'items', 'months' => $months]) }}">Export CSV</a>
@endsection

@section('content')

<section class="panel">
    <div class="table-wrap">
        <table class="table adm-table">
            <thead><tr><th class="num">#</th><th>Item</th><th>Description</th><th class="num">Pieces</th><th class="num">Orders</th><th class="num">Revenue</th></tr></thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="num muted">{{ $i + 1 }}</td>
                        <td class="mono nw">{{ $row['sku'] }}</td>
                        <td><span class="trunc" style="max-width:280px">{{ $row['name'] }}</span></td>
                        <td class="num"><b>{{ number_format($row['pieces']) }}</b></td>
                        <td class="num muted">{{ number_format($row['orders']) }}</td>
                        <td class="num">{{ \App\Support\Money::format($row['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection
