<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checkout without a payment merchant, which is how this trade actually works:
 * the order is submitted against the account's terms, a rep confirms it, and
 * an invoice follows. What checkout captures is everything needed to pick,
 * pack and bill — never a card.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $user = $request->user();
        $company = $user->company;

        $this->authorize('create', Order::class);

        return view('checkout.show', [
            'lines' => $this->cart->lines($company),
            'totals' => $this->cart->totals($company),
            'issues' => $this->cart->issues($company),
            'addresses' => $company->addresses()->defaultFirst()->get(),
            'warehouses' => Warehouse::availableTo($company)->get(),
            'shippingServices' => [
                'Ground — 3 to 5 business days',
                'Two-day air',
                'Next-day air',
                'Customer collect',
                'Customer\'s carrier account',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $company = $request->user()->company;
        $totals = $this->cart->totals($company);

        $data = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'shipping_service' => ['required', 'string', 'max:120'],
            'in_hands_on' => ['nullable', 'date', 'after:today'],
            'job_reference' => ['nullable', 'string', 'max:120'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],

            // Above the configured threshold a PO number is how the customer's
            // own finance team will match the invoice, so it is required.
            'customer_po' => [
                $totals->requiresPurchaseOrder() ? 'required' : 'nullable',
                'string', 'max:64',
            ],
        ], [
            'customer_po.required' => sprintf(
                'Orders over %s need your purchase order number.',
                \App\Support\Money::format(config('tbm.storefront.require_po_over'))
            ),
        ]);

        $order = $this->orders->placeFromCart(
            $request->user(),
            Address::findOrFail($data['address_id']),
            Warehouse::findOrFail($data['warehouse_id']),
            $data,
        );

        return redirect()->route('checkout.confirmed', $order);
    }

    public function confirmed(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('checkout.confirmed', [
            'order' => $order->load('items', 'warehouse'),
        ]);
    }
}
