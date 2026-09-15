<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock watch: the items this account actually buys, with what is on the shelf
 * right now. A buyer with a repeat programme wants one page that answers "can
 * I promise this next week", not a search across the whole catalogue.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        // Items this account has bought before, most recent first.
        $skus = $company->orders()
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->orderByDesc('orders.placed_at')
            ->pluck('order_items.sku')
            ->unique()
            ->take(60);

        $products = Product::published()
            ->with(['inventoryLevels.warehouse', 'colourways'])
            ->when($skus->isNotEmpty() && ! $request->boolean('all'), fn ($q) => $q->whereIn('sku', $skus))
            ->search($request->input('q'))
            ->ordered()
            ->get();

        return view('account.inventory', [
            'products' => $products,
            'warehouses' => Warehouse::onStorefront()->get(),
            'showingAll' => $request->boolean('all') || $skus->isEmpty(),
            'hasHistory' => $skus->isNotEmpty(),
            'filters' => $request->only(['q', 'all']),
        ]);
    }
}
