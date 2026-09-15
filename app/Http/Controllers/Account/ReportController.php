<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "We should have the history to pull out customers data — item wise, colour
 * wise, month wise." This is that, for the customer's own account.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        $months = (int) $request->integer('months', 12);
        $from = now()->subMonths($months);

        return view('account.reports', [
            'months' => $months,
            'headline' => $this->reports->headline($company, $months),
            'byMonth' => $this->reports->byMonth($company, $months),
            'byItem' => $this->reports->byItem($company, $from, 20),
            'byColour' => $this->reports->byColour($company, $from),
            'byCategory' => $this->reports->byCategory($company, $from),
            'byDecoration' => $this->reports->byDecoration($company, $from),
            'byUser' => $this->reports->byUser($company, $from),
            'byWarehouse' => $this->reports->byWarehouse($company, $from),
        ]);
    }

    /**
     * CSV rather than a spreadsheet library: it opens in everything, it is
     * streamed so a long history never exhausts memory, and it is what a
     * finance team will paste into their own model anyway.
     */
    public function export(Request $request): StreamedResponse
    {
        $company = $request->user()->company;
        $view = $request->input('view', 'item');
        $from = now()->subMonths((int) $request->integer('months', 12));

        [$headers, $rows] = $this->dataset($view, $company, $from);

        $filename = sprintf('%s-%s-%s.csv', $company->slug, $view, Carbon::now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function dataset(string $view, $company, Carbon $from): array
    {
        return match ($view) {
            'colour' => [['Colour', 'Pieces', 'Value'], $this->reports->byColour($company, $from)->all()],
            'month' => [['Month', 'Orders', 'Pieces', 'Value'], $this->reports->byMonth($company, 24)
                ->map(fn ($r) => ['month' => $r['label'], 'orders' => $r['orders'], 'pieces' => $r['pieces'], 'value' => $r['value']])
                ->all()],
            'category' => [['Category', 'Pieces', 'Value'], $this->reports->byCategory($company, $from)->all()],
            'user' => [['Buyer', 'Orders', 'Value'], $this->reports->byUser($company, $from)
                ->map(fn ($r) => ['name' => $r['name'], 'orders' => $r['orders'], 'value' => $r['value']])
                ->all()],
            default => [['Item number', 'Description', 'Pieces', 'Orders', 'Value'], $this->reports->byItem($company, $from, 500)
                ->map(fn ($r) => ['sku' => $r['sku'], 'name' => $r['name'], 'pieces' => $r['pieces'], 'orders' => $r['orders'], 'value' => $r['value']])
                ->all()],
        };
    }
}
