<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless($product->is_published || $request->user()?->can('catalogue.manage'), 404);

        $product->load(['category', 'colourways', 'inventoryLevels.warehouse']);

        $company = $request->user()?->company;

        return view('product.show', [
            'product' => $product,
            'ladder' => $request->user()?->canSeePricing()
                ? $this->pricing->ladder($product, $company)
                : null,
            'decorationMethods' => $this->decorationMethods($product),
            'related' => Product::published()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->with(['colourways', 'inventoryLevels.warehouse'])
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Live price for the quantity stepper on the item page.
     *
     * It exists so the figure changes as the buyer types rather than after a
     * page load — at these quantities the break they land on is the decision
     * they are actually making.
     */
    public function quote(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()->canSeePricing(), 403);

        $quantity = max(1, (int) $request->integer('quantity', $product->moq));
        $company = $request->user()->company;

        $quote = $this->pricing->quote($product, $company, $quantity);

        return response()->json([
            'quantity' => $quantity,
            'unit_price' => $quote->unitPrice,
            'unit_price_formatted' => \App\Support\Money::unit($quote->unitPrice),
            'line_total' => $quote->lineTotal(),
            'line_total_formatted' => \App\Support\Money::format($quote->lineTotal()),
            'saving_percent' => $quote->savingPercent(),
            'next_break' => $this->pricing->nextBreak($product, $company, $quantity),
            'available' => $product->totalStock(),
        ]);
    }

    /**
     * Sublimation only takes on polyester, so it is offered only where it will
     * actually work rather than disappointing someone at artwork stage.
     */
    private function decorationMethods(Product $product): array
    {
        return array_map(function (array $method) use ($product) {
            $requires = $method['requires_material'] ?? null;

            $method['available'] = $requires === null
                || str_contains(strtolower($product->material), strtolower($requires));

            return $method;
        }, config('tbm.decoration.methods'));
    }
}
