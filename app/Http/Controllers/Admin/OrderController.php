<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        return view('admin.orders.index', [
            'orders' => $this->query($request)->paginate(30)->withQueryString(),
            'statuses' => OrderStatus::options(),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::active()->get(),
            'filters' => $request->only(['status', 'company', 'warehouse', 'q']),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', [
            'order' => $order->load(['items.product', 'company.tier', 'placedBy', 'warehouse', 'notes.user']),
        ]);
    }

    /* ------------------------------------------------------- Transitions */

    public function confirm(Order $order): RedirectResponse
    {
        $this->orders->confirm($order, auth()->user());

        return back()->with('status', sprintf(
            '%s confirmed. Stock is now allocated at %s.',
            $order->reference,
            $order->warehouse?->code ?? 'the warehouse'
        ));
    }

    public function production(Order $order): RedirectResponse
    {
        $this->orders->markInProduction($order);

        return back()->with('status', $order->reference.' moved to production.');
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['shipping_service' => ['nullable', 'string', 'max:120']]);

        $this->orders->markShipped($order, $data['shipping_service'] ?? null);

        return back()->with('status', sprintf(
            '%s marked shipped. Stock has come off the shelf at %s.',
            $order->reference,
            $order->warehouse?->code ?? 'the warehouse'
        ));
    }

    public function deliver(Order $order): RedirectResponse
    {
        $this->orders->markDelivered($order);

        return back()->with('status', $order->reference.' marked delivered.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->orders->cancel($order, $data['reason'], $request->user());

        return back()->with('status', $order->reference.' cancelled and any allocation released.');
    }

    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $internal = $request->boolean('is_internal', true);

        if (! $internal) {
            $this->authorize('update', $order);
        }

        $order->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => $internal,
        ]);

        return back()->with('status', $internal
            ? 'Internal note added. The customer will not see it.'
            : 'Note added and visible to the customer.');
    }

    /**
     * The pick list — the one customer-facing-adjacent document that carries
     * the mill reference, because the warehouse picks by it. Staff only, and
     * the policy says so rather than the route alone.
     */
    public function pickList(Order $order): View
    {
        $this->authorize('viewPickList', $order);

        return view('admin.orders.pick-list', [
            'order' => $order->load(['items.product', 'warehouse', 'company']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->query($request)->with(['company', 'warehouse'])->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Account', 'Account number', 'PO', 'Pieces', 'Merchandise', 'Total', 'Warehouse', 'Status']);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->reference,
                    $order->placed_at?->format('Y-m-d'),
                    $order->company->name,
                    $order->company->account_number,
                    $order->customer_po,
                    $order->total_pieces,
                    $order->merchandise_total,
                    $order->grand_total,
                    $order->warehouse?->code,
                    $order->status->label(),
                ]);
            }

            fclose($out);
        }, 'tbm-orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        return Order::query()
            ->with(['company', 'warehouse', 'placedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('warehouse'), fn ($q) => $q->where('warehouse_id', $request->input('warehouse')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->input('q').'%';
                $q->where(fn ($w) => $w
                    ->where('reference', 'like', $like)
                    ->orWhere('customer_po', 'like', $like)
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('items', fn ($i) => $i->where('sku', 'like', $like)->orWhere('parent_sku', 'like', $like)));
            })
            ->latestFirst();
    }
}
