<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImportType;
use App\Http\Controllers\Controller;
use App\Models\StockImport;
use App\Services\Imports\ImportException;
use App\Services\Imports\StockImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The morning sheet, in three steps: upload, map the columns, review what will
 * change. Only the last step writes a stock figure, and it is the one carrying
 * the imports.apply permission.
 */
class ImportController extends Controller
{
    public function __construct(
        private readonly StockImportService $imports,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', StockImport::class);

        return view('admin.imports.index', [
            'imports' => StockImport::with('user')->latestFirst()->paginate(25),
            'types' => ImportType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockImport::class);

        return view('admin.imports.create', [
            'types' => ImportType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockImport::class);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'type' => ['required', Rule::in(array_column(ImportType::cases(), 'value'))],
        ]);

        $import = $this->imports->stage(
            $request->file('file'),
            ImportType::from($data['type']),
            $request->user(),
        );

        return redirect()->route('admin.imports.map', $import);
    }

    public function map(StockImport $import): View
    {
        $this->authorize('create', StockImport::class);

        return view('admin.imports.map', [
            'import' => $import,
            'headers' => $this->imports->headers($import),
            'suggested' => $import->column_map ?? $this->imports->suggestMapping($import),
            'fields' => $this->fields(),
        ]);
    }

    public function preview(Request $request, StockImport $import): RedirectResponse
    {
        $this->authorize('create', StockImport::class);

        $data = $request->validate([
            'map' => ['required', 'array'],
            'map.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $map = array_filter($data['map'], fn ($v) => $v !== null && $v !== '');

        if (! isset($map['parent_sku'])) {
            throw new ImportException('Map the mill reference column — without it no line can be matched to an item.');
        }

        $this->imports->preview($import, $map);

        return redirect()->route('admin.imports.show', $import);
    }

    public function show(Request $request, StockImport $import): View
    {
        $this->authorize('view', $import);

        return view('admin.imports.show', [
            'import' => $import->load('user'),
            'rows' => $import->rows()
                ->with(['product', 'warehouse', 'colourway'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
                ->orderBy('line_number')
                ->paginate(100)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'counts' => $import->rows()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function apply(Request $request, StockImport $import): RedirectResponse
    {
        $this->authorize('apply', $import);

        $this->imports->apply($import, $request->user());
        $import->refresh();

        return back()->with('status', sprintf(
            '%s applied. %s stock figures are now live on the storefront%s.',
            $import->reference,
            number_format($import->rows_updated),
            $import->warnings > 0 ? sprintf(', with %s rows left for review', number_format($import->warnings)) : ''
        ));
    }

    public function rollBack(Request $request, StockImport $import): RedirectResponse
    {
        $this->authorize('rollBack', $import);

        $this->imports->rollBack($import, $request->user());

        return back()->with('status', sprintf(
            '%s rolled back. Every figure it touched is back to what it was.',
            $import->reference
        ));
    }

    /** The domain fields a source column can be mapped onto. */
    private function fields(): array
    {
        return [
            'parent_sku' => ['label' => 'Mill reference', 'required' => true, 'note' => 'The mill\'s own code. Matched to an item number here — the customer never sees it.'],
            'description' => ['label' => 'Description', 'note' => 'Used only to help you spot a mismatch.'],
            'shade' => ['label' => 'Colour', 'note' => 'Matched on our colour name or the mill\'s.'],
            'warehouse_code' => ['label' => 'Warehouse', 'required' => true, 'note' => 'Must match one of our warehouse codes.'],
            'quantity' => ['label' => 'Quantity on hand', 'required' => true, 'note' => 'Replaces the current figure. Not added to it.'],
            'cost' => ['label' => 'FOB cost', 'note' => 'Feeds margin reporting. Never shown to a customer.'],
            'ready_on' => ['label' => 'Ready / ETA date', 'note' => 'Shown as the intake date on out-of-stock items.'],
        ];
    }
}
