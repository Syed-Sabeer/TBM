<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Reporting\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stock, as the warehouse sees it. Everything written here goes through
 * InventoryService, so every correction leaves a movement behind saying what
 * the figure was and who changed it.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $warehouses = Warehouse::active()->orderBy('position')->get();

        $products = Product::query()
            ->with(['inventoryLevels.warehouse'])
            ->search($request->input('q'), includeMillReference: true)
            ->ordered()
            ->get();

        // Demand per item, grossed up by the configured scale so cover reads
        // against the whole customer base rather than the sample in the table.
        $usage = $this->reports->byItem(null, now()->subYear(), 500)
            ->mapWithKeys(fn ($row) => [
                $row['sku'] => ($row['pieces'] / 12) * (float) config('tbm.demand_scale'),
            ]);

        $rows = $products->map(fn (Product $p) => [
            'product' => $p,
            'monthly' => $monthly = (float) ($usage[$p->sku] ?? 0),
            'cover' => $this->inventory->weeksOfCover($p, $monthly),
        ]);

        $rows = $this->applyView($rows, $request->input('view'));

        return view('admin.inventory.index', [
            'rows' => $rows,
            'warehouses' => $warehouses,
            'filters' => $request->only(['q', 'warehouse', 'view']),
            'totals' => [
                'units' => $products->sum(fn (Product $p) => $p->totalStock()),
                'value' => $products->sum(fn (Product $p) => $p->totalStock() * $p->effectiveCost()),
                'low' => $rows->where('cover', '<', 10)->count(),
                'out' => $products->filter(fn (Product $p) => $p->inventoryLevels->contains(fn ($l) => $l->available() === 0))->count(),
            ],
        ]);
    }

    /**
     * Bulk corrections from the editable grid. Each cell that actually changed
     * becomes its own movement with its own reason — a blanket "bulk edit"
     * entry would be useless six months later.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.product_id' => ['required', 'exists:products,id'],
            'changes.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'changes.*.quantity' => ['required', 'integer', 'min:0'],
            'changes.*.reason' => ['nullable', 'string', 'max:64'],
        ]);

        $applied = 0;

        foreach ($data['changes'] as $change) {
            $product = Product::find($change['product_id']);
            $warehouse = Warehouse::find($change['warehouse_id']);

            if (! $product || ! $warehouse) {
                continue;
            }

            $this->inventory->setOnHand(
                $product,
                $warehouse,
                (int) $change['quantity'],
                $this->reasonCode($change['reason'] ?? null),
                $request->user(),
                null,
                $change['reason'] ?? null,
            );

            $applied++;
        }

        return back()->with('status', sprintf(
            '%d stock %s applied and published to the storefront.',
            $applied,
            \Illuminate\Support\Str::plural('correction', $applied)
        ));
    }

    public function transfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $from = Warehouse::findOrFail($data['from_warehouse_id']);
        $to = Warehouse::findOrFail($data['to_warehouse_id']);

        $this->inventory->transfer($product, $from, $to, (int) $data['quantity'], $request->user(), $data['note'] ?? null);

        return back()->with('status', sprintf(
            '%s pieces of %s moved from %s to %s.',
            number_format($data['quantity']),
            $product->sku,
            $from->code,
            $to->code
        ));
    }

    public function movements(Request $request): View
    {
        return view('admin.inventory.movements', [
            'movements' => StockMovement::query()
                ->with(['product', 'warehouse', 'user'])
                ->when($request->filled('sku'), fn ($q) => $q->whereHas(
                    'product',
                    fn ($p) => $p->where('sku', $request->input('sku'))->orWhere('parent_sku', $request->input('sku'))
                ))
                ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->input('reason')))
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString(),
            'filters' => $request->only(['sku', 'reason']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $warehouses = Warehouse::active()->orderBy('position')->get();
        $products = Product::with('inventoryLevels.warehouse')->ordered()->get();

        return response()->streamDownload(function () use ($products, $warehouses) {
            $out = fopen('php://output', 'w');

            // The export is for internal use, so it carries both identifiers.
            fputcsv($out, array_merge(
                ['Mill reference', 'Item number', 'Description'],
                $warehouses->pluck('code')->all(),
                ['Total']
            ));

            foreach ($products as $product) {
                fputcsv($out, array_merge(
                    [$product->parent_sku, $product->sku, $product->name],
                    $warehouses->map(fn ($w) => $product->stockAt($w))->all(),
                    [$product->totalStock()]
                ));
            }

            fclose($out);
        }, 'tbm-stock-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function applyView($rows, ?string $view)
    {
        return match ($view) {
            'low' => $rows->where('cover', '<', 10)->values(),
            'deep' => $rows->where('cover', '>=', 26)->values(),
            'out' => $rows->filter(fn ($r) => $r['product']->inventoryLevels->contains(fn ($l) => $l->available() === 0))->values(),
            default => $rows,
        };
    }

    private function reasonCode(?string $reason): string
    {
        return match ($reason) {
            'Damage write-off' => StockMovement::REASON_DAMAGE,
            'Receipt not on the sheet' => StockMovement::REASON_RECEIPT,
            'Customer return' => StockMovement::REASON_RETURN,
            'Transfer' => StockMovement::REASON_TRANSFER,
            default => StockMovement::REASON_CYCLE_COUNT,
        };
    }
}
