<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Colourway;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The catalogue, and the one screen where the two identifiers are managed
 * side by side: the mill reference the sheet speaks in, and the item number
 * the customer sees. Re-source an item from a different mill and only the
 * former changes — every historical document still reads correctly.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        return view('admin.products.index', [
            'products' => Product::query()
                ->with(['category', 'inventoryLevels'])
                ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->input('category'))))
                ->when($request->filled('state'), fn ($q) => $q->where('is_published', $request->input('state') === 'live'))
                ->search($request->input('q'), includeMillReference: true)
                ->ordered()
                ->paginate(40)
                ->withQueryString(),
            'categories' => Category::orderBy('position')->get(),
            'filters' => $request->only(['q', 'category', 'state']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(['moq' => 50, 'order_step' => 25, 'carton_quantity' => 100, 'is_published' => true]),
            'categories' => Category::orderBy('position')->get(),
            'colourways' => Colourway::orderBy('position')->get(),
            'shapes' => \App\Services\Rendering\BagRenderer::SHAPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::create($data);
        $product->colourways()->sync($request->input('colourways', []));

        ActivityLog::record(
            'Product added',
            sprintf('%s (mill reference %s)', $product->sku, $product->parent_sku),
            $product
        );

        return redirect()->route('admin.products.edit', $product)->with('status', $product->sku.' added.');
    }

    public function edit(Request $request, Product $product): View
    {
        $tiers = \App\Models\PriceTier::orderBy('position')->get();

        return view('admin.products.form', [
            'product' => $product->load(['colourways', 'inventoryLevels.warehouse']),
            'categories' => Category::orderBy('position')->get(),
            'colourways' => Colourway::orderBy('position')->get(),
            'shapes' => \App\Services\Rendering\BagRenderer::SHAPES,
            'matrix' => $this->pricing->matrix($product, $tiers),
            'tiers' => $tiers,
            'breaks' => $this->pricing->breakQuantities(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $wasParent = $product->parent_sku;
        $product->update($this->validated($request, $product));
        $product->colourways()->sync($request->input('colourways', []));

        if ($wasParent !== $product->parent_sku) {
            // Worth its own log line: the morning import matches on this.
            ActivityLog::record(
                'Mill reference changed',
                sprintf('%s: %s → %s', $product->sku, $wasParent, $product->parent_sku),
                $product
            );
        }

        return back()->with('status', $product->sku.' saved.');
    }

    public function export(): StreamedResponse
    {
        $products = Product::with('category')->ordered()->get();

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Mill reference', 'Item number', 'Name', 'Category', 'Material', 'Base price', 'Cost', 'MOQ', 'Published']);

            foreach ($products as $p) {
                fputcsv($out, [
                    $p->parent_sku, $p->sku, $p->name, $p->category->name,
                    $p->material, $p->base_price, $p->cost_price, $p->moq,
                    $p->is_published ? 'yes' : 'no',
                ]);
            }

            fclose($out);
        }, 'tbm-catalogue-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'parent_sku' => ['required', 'string', 'max:32', Rule::unique('products', 'parent_sku')->ignore($product?->id)],
            'sku' => ['required', 'string', 'max:32', Rule::unique('products', 'sku')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'shape' => ['required', Rule::in(\App\Services\Rendering\BagRenderer::SHAPES)],
            'material' => ['required', 'string', 'max:120'],
            'fabric_weight' => ['nullable', 'string', 'max:32'],
            'sizes' => ['required', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'moq' => ['required', 'integer', 'min:1'],
            'carton_quantity' => ['required', 'integer', 'min:1'],
            'order_step' => ['required', 'integer', 'min:1'],
            'origin' => ['nullable', 'string', 'max:80'],
            'is_published' => ['nullable', 'boolean'],
            'flags' => ['nullable', 'array'],
        ]);

        // Sizes are typed one per line, which is faster than a repeater and
        // reads the same way as the spec sheet they are copied from.
        $data['sizes'] = collect(preg_split('/\r?\n/', $data['sizes']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(fn ($line) => ['label' => $line])
            ->values()
            ->all();

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
