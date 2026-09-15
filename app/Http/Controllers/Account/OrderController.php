<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The shared order history.
 *
 * Every query here is scoped to the company, not to the signed-in person —
 * which is the behaviour the business asked for: any contact can place an
 * order, and everyone on the account sees all of them.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;

        $orders = Order::forCompany($company)
            ->with(['placedBy', 'warehouse'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('user'), fn ($q) => $q->where('placed_by_id', $request->input('user')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->input('q').'%';
                $q->where(fn ($w) => $w
                    ->where('reference', 'like', $like)
                    ->orWhere('customer_po', 'like', $like)
                    ->orWhere('job_reference', 'like', $like)
                    ->orWhereHas('items', fn ($i) => $i->where('sku', 'like', $like)));
            })
            ->latestFirst()
            ->paginate(20)
            ->withQueryString();

        return view('account.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::options(),
            'colleagues' => $company->users()->orderBy('name')->get(),
            'filters' => $request->only(['status', 'user', 'q']),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('account.orders.show', [
            'order' => $order->load(['items.product', 'warehouse', 'placedBy', 'customerNotes.user']),
        ]);
    }

    public function reorder(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('reorder', $order);

        $added = $this->orders->reorder($order);

        return redirect()->route('cart.index')->with('status', $added === $order->items->count()
            ? sprintf('%s lines from %s added at today\'s prices.', $added, $order->reference)
            : sprintf('%s of %s lines added — the rest are no longer in the catalogue.', $added, $order->items->count()));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->orders->cancel($order, $data['reason'], $request->user());

        return back()->with('status', sprintf('%s has been withdrawn.', $order->reference));
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorize('downloadDocuments', $order);

        return view('documents.invoice', [
            'order' => $order->load('items', 'company'),
        ]);
    }

    public function packingSlip(Request $request, Order $order): View
    {
        $this->authorize('downloadDocuments', $order);

        return view('documents.packing-slip', [
            'order' => $order->load('items', 'company', 'warehouse'),
        ]);
    }
}
