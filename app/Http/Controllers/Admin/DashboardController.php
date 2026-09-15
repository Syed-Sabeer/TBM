<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ImportStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockImport;
use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What needs attention this morning, in the order it needs it: orders waiting
 * on a person, accounts waiting on approval, and whether last night's import
 * actually landed.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        return view('admin.dashboard', [
            'headline' => $this->reports->headline(null, 12),
            'byMonth' => $this->reports->byMonth(null, 12),
            'topCompanies' => $this->reports->byCompany(now()->subYear(), 8),
            'topItems' => $this->reports->byItem(null, now()->subYear(), 8),

            'awaitingConfirmation' => Order::open()
                ->where('status', \App\Enums\OrderStatus::PendingConfirmation->value)
                ->with('company')
                ->latestFirst()
                ->limit(8)
                ->get(),

            'pendingAccounts' => Company::awaitingApproval()
                ->withCount('users')
                ->latest()
                ->limit(6)
                ->get(),

            'lastImport' => StockImport::latestFirst()->first(),
            'importsWaiting' => StockImport::where('status', ImportStatus::Previewed->value)->count(),

            'stockAlerts' => $this->stockAlerts(),

            'counts' => [
                'accounts' => Company::count(),
                'active' => Company::where('status', AccountStatus::Active->value)->count(),
                'skus' => Product::published()->count(),
                'units' => (int) InventoryLevel::sum('on_hand'),
            ],

            'activity' => ActivityLog::with('user')->latestFirst()->limit(12)->get(),
        ]);
    }

    /**
     * Items with nothing left in at least one warehouse. Deliberately simple:
     * a reorder decision needs a human looking at the demand, and a clever
     * threshold here would only hide the ones that matter.
     */
    private function stockAlerts()
    {
        return Product::published()
            ->with(['inventoryLevels.warehouse'])
            ->get()
            ->filter(fn (Product $p) => $p->inventoryLevels->contains(fn ($l) => $l->available() === 0))
            ->sortBy(fn (Product $p) => $p->totalStock())
            ->take(8)
            ->values();
    }
}
