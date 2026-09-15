<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Pricing\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function home(Request $request): View
    {
        $this->pricing->warmFor($request->user()?->company);

        return view('pages.home', [
            'featured' => Product::published()
                ->whereJsonContains('flags', 'bestseller')
                ->with(['colourways', 'inventoryLevels.warehouse'])
                ->ordered()
                ->limit(8)
                ->get(),
            'newest' => Product::published()
                ->whereJsonContains('flags', 'new')
                ->with(['colourways', 'inventoryLevels.warehouse'])
                ->limit(4)
                ->get(),
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('position')
                ->limit(8)
                ->get(),
            'warehouses' => Warehouse::onStorefront()->get(),
            'totalStock' => (int) \App\Models\InventoryLevel::sum('on_hand'),
            'skuCount' => Product::published()->count(),
        ]);
    }

    public function customization(): View
    {
        return view('pages.customization', [
            'methods' => config('tbm.decoration.methods'),
        ]);
    }

    public function story(): View
    {
        return view('pages.story');
    }

    public function sustainability(): View
    {
        return view('pages.sustainability');
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'warehouses' => Warehouse::onStorefront()->get(),
        ]);
    }

    /**
     * Enquiries are logged and acknowledged rather than mailed from here —
     * wiring a mailer is a deployment decision, and a form that silently fails
     * to send is worse than one that says what it did.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:4000'],
        ]);

        \App\Models\ActivityLog::record(
            'Website enquiry',
            sprintf(
                '%s <%s>%s — %s',
                $data['name'] ?? 'Anonymous',
                $data['email'],
                isset($data['company']) ? ' of '.$data['company'] : '',
                $data['topic'] ?? 'General'
            )
        );

        return back()->with('status', 'Thanks — a rep will come back to you within one business day.');
    }
}
