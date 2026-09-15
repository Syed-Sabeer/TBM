<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The account's own rate card: every item, at every break, at their numbers.
 *
 * A wholesale buyer builds quotes for their end clients out of this, so it is
 * a page rather than something they have to ask a rep for.
 */
class PricingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->canSeePricing(), 403);

        $company = $request->user()->company;
        $this->pricing->warmFor($company);

        $products = Product::published()
            ->with('category')
            ->when($request->filled('category'), fn ($q) => $q->whereHas(
                'category',
                fn ($c) => $c->where('slug', $request->input('category'))
            ))
            ->search($request->input('q'))
            ->ordered()
            ->get();

        return view('account.pricing', [
            'company' => $company,
            'rows' => $products->map(fn (Product $product) => [
                'product' => $product,
                'ladder' => $this->pricing->ladder($product, $company),
                'negotiated' => $this->pricing->overrideFactorFor($product, $company) !== 1.0,
            ]),
            'breaks' => $this->pricing->breakQuantities(),
            'categories' => Category::orderBy('position')->get(),
            'filters' => $request->only(['q', 'category']),
        ]);
    }
}
