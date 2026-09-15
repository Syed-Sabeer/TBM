<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $months = (int) $request->integer('months', 12);
        $from = now()->subMonths($months);

        return view('admin.reports.index', [
            'months' => $months,
            'headline' => $this->reports->headline(null, $months),
            'byMonth' => $this->reports->byMonth(null, $months),
            'byCompany' => $this->reports->byCompany($from, 12),
            'byItem' => $this->reports->byItem(null, $from, 12),
            'byColour' => $this->reports->byColour(null, $from),
            'byCategory' => $this->reports->byCategory(null, $from),
            'byWarehouse' => $this->reports->byWarehouse(null, $from),
            'byDecoration' => $this->reports->byDecoration(null, $from),
        ]);
    }

    public function customers(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $months = (int) $request->integer('months', 12);

        return view('admin.reports.customers', [
            'months' => $months,
            'rows' => $this->reports->byCompany(now()->subMonths($months), 100),
        ]);
    }

    public function items(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $months = (int) $request->integer('months', 12);

        return view('admin.reports.items', [
            'months' => $months,
            'rows' => $this->reports->byItem(null, now()->subMonths($months), 200),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $months = (int) $request->integer('months', 12);
        $from = now()->subMonths($months);
        $view = $request->input('view', 'customers');

        [$headers, $rows] = match ($view) {
            'items' => [['Item number', 'Description', 'Pieces', 'Orders', 'Value'],
                $this->reports->byItem(null, $from, 1000)->map(fn ($r) => [$r['sku'], $r['name'], $r['pieces'], $r['orders'], $r['value']])],
            'months' => [['Month', 'Orders', 'Pieces', 'Value'],
                $this->reports->byMonth(null, 24)->map(fn ($r) => [$r['label'], $r['orders'], $r['pieces'], $r['value']])],
            default => [['Account', 'Account number', 'Orders', 'Pieces', 'Value'],
                $this->reports->byCompany($from, 1000)->map(fn ($r) => [$r['name'], $r['account_number'], $r['orders'], $r['pieces'], $r['value']])],
        };

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_values((array) $row));
            }

            fclose($out);
        }, sprintf('tbm-%s-%s.csv', $view, now()->format('Y-m-d')), ['Content-Type' => 'text/csv']);
    }
}
