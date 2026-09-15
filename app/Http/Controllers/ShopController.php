<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Colourway;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Pricing\PricingService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The catalogue.
 *
 * Open to anyone: the goods, the specifications and the stock are all public,
 * because that is what a buyer needs to shortlist. Only the price is behind
 * the login, and that gate lives in the price component rather than here.
 */
class ShopController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, Category $category): View
    {
        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?Category $category): View
    {
        $query = Product::query()
            ->published()
            ->with(['category', 'colourways', 'inventoryLevels.warehouse'])
            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
            ->search(
                $request->input('q'),
                // Staff searching the storefront may use mill references.
                includeMillReference: (bool) $request->user()?->isStaff()
            );

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->input('sort'));

        // One query for the account's negotiated rates instead of one per card.
        $this->pricing->warmFor($request->user()?->company);

        $products = $query
            ->paginate(config('tbm.storefront.per_page'))
            ->withQueryString();

        return view('shop.index', [
            'products' => $products,
            'category' => $category,
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('position')
                ->get()
                ->groupBy('group'),
            'colourways' => Colourway::orderBy('position')->get(),
            'warehouses' => Warehouse::onStorefront()->get(),
            'filters' => $request->only(['q', 'colour', 'warehouse', 'flag', 'material', 'sort', 'in_stock']),
            'sorts' => $this->sortOptions(),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('colour'), fn (Builder $q) => $q->whereHas(
                'colourways',
                fn (Builder $c) => $c->where('slug', $request->input('colour'))
            ))
            ->when($request->filled('material'), fn (Builder $q) => $q->where('material', 'like', '%'.$request->input('material').'%'))
            ->when($request->filled('flag'), fn (Builder $q) => $q->whereJsonContains('flags', $request->input('flag')));

        // "Ready in Los Angeles" means stock on the shelf there, not a listing.
        if ($request->filled('warehouse')) {
            $query->whereHas('inventoryLevels', fn (Builder $l) => $l
                ->whereHas('warehouse', fn (Builder $w) => $w->where('code', $request->input('warehouse')))
                ->whereColumn('on_hand', '>', 'allocated'));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('inventoryLevels', fn (Builder $l) => $l->whereColumn('on_hand', '>', 'allocated'));
        }
    }

    private function applySort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            // Sorting by price is sorting by base price, which preserves the
            // order for every tier — the factors are uniform multipliers.
            'price-asc' => $query->orderBy('base_price'),
            'price-desc' => $query->orderByDesc('base_price'),
            'newest' => $query->orderByDesc('created_at'),
            'name' => $query->orderBy('name'),
            'stock' => $query->withSum('inventoryLevels as stock_total', 'on_hand')->orderByDesc('stock_total'),
            default => $query->ordered(),
        };
    }

    private function sortOptions(): array
    {
        return [
            '' => 'Featured',
            'price-asc' => 'Price, low to high',
            'price-desc' => 'Price, high to low',
            'stock' => 'Most in stock',
            'newest' => 'Newest',
            'name' => 'Name',
        ];
    }
}
