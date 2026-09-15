<?php

namespace App\Http\Controllers;

use App\Models\Colourway;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The basket is open to a signed-out visitor on purpose: they can build one,
 * and the prices simply do not render until they are an approved account. The
 * alternative — refusing to let them collect anything — loses the enquiry.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $request->user()?->company;

        return view('cart.index', [
            'lines' => $this->cart->lines($company),
            'totals' => $this->cart->totals($company),
            'issues' => $this->cart->issues($company),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'colourway_id' => ['nullable', 'exists:colourways,id'],
            'size' => ['required', 'string', 'max:64'],
            'decoration' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        $this->cart->add(
            $product,
            $data['colourway_id'] ? Colourway::find($data['colourway_id']) : null,
            $data['size'],
            $data['decoration'],
            (int) $data['quantity'],
        );

        return back()->with('status', sprintf(
            '%s added to your basket. Quantities round up to the %s-piece carton step.',
            $product->sku,
            number_format($product->order_step)
        ));
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000000']]);

        $this->cart->updateQuantity($key, (int) $request->integer('quantity'));

        return back();
    }

    public function destroy(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return back()->with('status', 'Line removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('status', 'Basket emptied.');
    }
}
