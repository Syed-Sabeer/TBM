<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('account.dashboard', [
            'company' => $company,
            'headline' => $this->reports->headline($company),
            'byMonth' => $this->reports->byMonth($company, 12),
            'topItems' => $this->reports->byItem($company, now()->subYear(), 5),
            'openOrders' => Order::forCompany($company)
                ->open()
                ->with('items')
                ->latestFirst()
                ->limit(5)
                ->get(),
            'recentOrders' => Order::forCompany($company)
                ->with('placedBy')
                ->latestFirst()
                ->limit(6)
                ->get(),
        ]);
    }
}
