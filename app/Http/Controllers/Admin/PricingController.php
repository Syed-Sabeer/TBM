<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PriceOverride;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The rate card in one place: the tiers, what each is worth, and — per item —
 * the full matrix of what every tier pays at every break, with the margin
 * beside it so nobody sets a price below the floor by accident.
 */
class PricingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('pricing.view'), 403);

        $tiers = PriceTier::withCount('companies')->orderBy('position')->get();

        $products = Product::published()
            ->with('category')
            ->search($request->input('q'), includeMillReference: true)
            ->ordered()
            ->paginate(30)
            ->withQueryString();

        return view('admin.pricing.index', [
            'tiers' => $tiers,
            'products' => $products,
            'breaks' => $this->pricing->breakQuantities(),
            'matrix' => $products->mapWithKeys(fn (Product $p) => [
                $p->id => $this->pricing->matrix($p, $tiers),
            ]),
            'overrides' => PriceOverride::with(['company', 'product'])->latest()->limit(25)->get(),
            'filters' => $request->only('q'),
        ]);
    }

    public function updateTier(Request $request, PriceTier $tier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'factor' => ['required', 'numeric', 'min:0.4', 'max:1.5'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $was = $tier->factor;
        $tier->update($data);

        /*
         | Changing a tier factor reprices the catalogue for every account on
         | it, immediately. Historical orders are untouched, because their
         | money was captured at placement — but this is still a big lever,
         | which is why it is logged with both figures.
         */
        ActivityLog::record(
            'Tier factor changed',
            sprintf('%s: %s → %s, affecting %d accounts', $tier->fullName(), $was, $tier->factor, $tier->companies()->count()),
            $tier
        );

        return back()->with('status', sprintf(
            '%s updated. %d accounts now see prices at %s%% of the standard card.',
            $tier->fullName(),
            $tier->companies()->count(),
            number_format($tier->factor * 100, 1)
        ));
    }

    /** One item, every tier, every break, with margin — the price explainer. */
    public function matrix(Request $request, Product $product): View
    {
        abort_unless($request->user()->can('pricing.view'), 403);

        $tiers = PriceTier::orderBy('position')->get();

        return view('admin.pricing.matrix', [
            'product' => $product,
            'tiers' => $tiers,
            'matrix' => $this->pricing->matrix($product, $tiers),
            'breaks' => $this->pricing->breakQuantities(),
            'cost' => $product->effectiveCost(),
            'floor' => (float) config('tbm.margin_floor'),
            'overrides' => $product->priceOverrides()->with('company')->get(),
        ]);
    }
}
