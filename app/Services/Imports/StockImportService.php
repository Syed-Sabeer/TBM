<?php

namespace App\Services\Imports;

use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\ActivityLog;
use App\Models\Colourway;
use App\Models\Product;
use App\Models\StockImport;
use App\Models\StockImportRow;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The daily sheet from the mill.
 *
 * Three deliberate steps: stage, preview, apply. Staging parses the file into
 * rows and resolves each one against the catalogue; preview is where a human
 * sees what will change, including the lines that matched nothing; only apply
 * writes a stock figure. A wrong column map is therefore a wasted minute, not
 * a wrong storefront.
 *
 * The sheet speaks in mill references. Everything it says is translated into
 * customer item numbers here, and the mill reference goes no further.
 */
class StockImportService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {
    }

    /* --------------------------------------------------------------- Stage */

    public function stage(UploadedFile $file, ImportType $type, User $user): StockImport
    {
        if (! in_array(Str::lower($file->getClientOriginalExtension()), ['csv', 'txt', 'tsv'], true)) {
            throw new ImportException('Save the sheet as CSV and upload it again — .xlsx is not read directly.');
        }

        $path = $file->store('imports');

        return StockImport::create([
            'reference' => StockImport::nextReference(),
            'type' => $type,
            'status' => ImportStatus::Draft,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'user_id' => $user->id,
        ]);
    }

    public function headers(StockImport $import): array
    {
        return $this->reader($import)->read(1)['headers'];
    }

    public function suggestMapping(StockImport $import): array
    {
        $reader = $this->reader($import);

        return $reader->suggestMapping($reader->read(1)['headers']);
    }

    /* ------------------------------------------------------------- Preview */

    /**
     * Parse every line under the given column map and resolve it against the
     * catalogue. Nothing outside the stock_import_rows table is touched.
     *
     * @param  array<string, int>  $columnMap  domain field => column index
     */
    public function preview(StockImport $import, array $columnMap): StockImport
    {
        $sheet = $this->reader($import)->read();
        $rows = $sheet['rows'];

        $products = Product::all()->keyBy(fn (Product $p) => Str::upper($p->parent_sku));
        $warehouses = Warehouse::all()->keyBy(fn (Warehouse $w) => Str::upper($w->code));
        $colourways = Colourway::all();

        return DB::transaction(function () use ($import, $rows, $columnMap, $products, $warehouses, $colourways) {
            $import->rows()->delete();

            $counts = ['ready' => 0, 'unmatched' => 0, 'attention' => 0];

            foreach ($rows as $index => $row) {
                $values = $this->extract($row, $columnMap);

                $product = $values['parent_sku']
                    ? $products->get(Str::upper($values['parent_sku']))
                    : null;

                $warehouse = $values['warehouse_code']
                    ? $warehouses->get(Str::upper($values['warehouse_code']))
                    : null;

                $colourway = $values['shade']
                    ? $this->matchColourway($colourways, $values['shade'])
                    : null;

                [$status, $message] = $this->classify($values, $product, $warehouse, $colourway);

                $before = ($product && $warehouse)
                    ? (int) ($product->inventoryLevels
                        ->firstWhere('warehouse_id', $warehouse->id)?->on_hand ?? 0)
                    : null;

                StockImportRow::create([
                    'stock_import_id' => $import->id,
                    'line_number' => $index + 2,     // +1 for the header, +1 for 1-based
                    'parent_sku' => $values['parent_sku'],
                    'description' => $values['description'],
                    'shade' => $values['shade'],
                    'warehouse_code' => $values['warehouse_code'],
                    'quantity' => $values['quantity'],
                    'cost' => $values['cost'],
                    'ready_on' => $values['ready_on'],
                    'product_id' => $product?->id,
                    'warehouse_id' => $warehouse?->id,
                    'colourway_id' => $colourway?->id,
                    'quantity_before' => $before,
                    'status' => $status,
                    'message' => $message,
                    'raw' => $row,
                ]);

                $status === ImportRowStatus::Ready ? $counts['ready']++ : $counts['unmatched']++;

                if ($status->needsAttention()) {
                    $counts['attention']++;
                }
            }

            $import->forceFill([
                'status' => ImportStatus::Previewed,
                'column_map' => $columnMap,
                'rows_read' => count($rows),
                'rows_skipped' => $counts['unmatched'],
                'warnings' => $counts['attention'],
            ])->save();

            return $import->refresh();
        });
    }

    /* --------------------------------------------------------------- Apply */

    /**
     * Write the ready rows. Each one goes through InventoryService, so each
     * one leaves a movement carrying the figure it replaced — which is what
     * makes the run reversible.
     */
    public function apply(StockImport $import, User $user): StockImport
    {
        if (! $import->canBeApplied()) {
            throw new ImportException('This run has nothing ready to apply.');
        }

        $updated = 0;

        DB::transaction(function () use ($import, $user, &$updated) {
            $rows = $import->rows()
                ->ready()
                ->with(['product', 'warehouse'])
                ->get();

            foreach ($rows as $row) {
                if (! $row->product || ! $row->warehouse) {
                    continue;
                }

                if ($import->type === ImportType::Cost) {
                    $row->product->forceFill(['cost_price' => $row->cost])->save();
                } else {
                    $this->inventory->setOnHand(
                        $row->product,
                        $row->warehouse,
                        (int) $row->quantity,
                        StockMovement::REASON_IMPORT,
                        $user,
                        $import,
                        sprintf('%s line %d', $import->filename, $row->line_number)
                    );

                    if ($row->cost !== null) {
                        $row->product->forceFill(['cost_price' => $row->cost])->save();
                    }

                    if ($row->ready_on) {
                        $row->product->inventoryLevels()
                            ->where('warehouse_id', $row->warehouse_id)
                            ->update(['next_intake_on' => $row->ready_on]);
                    }
                }

                $row->forceFill(['status' => ImportRowStatus::Applied])->save();
                $updated++;
            }

            $import->forceFill([
                'status' => $import->warnings > 0
                    ? ImportStatus::AppliedWithWarnings
                    : ImportStatus::Applied,
                'rows_updated' => $updated,
                'applied_at' => now(),
            ])->save();
        });

        ActivityLog::record(
            'Stock import applied',
            sprintf('%s — %s rows updated from %s', $import->reference, number_format($updated), $import->filename),
            $import
        );

        return $import->refresh();
    }

    /**
     * Undo a run by replaying its movements backwards. Only safe while nothing
     * later has touched the same lines, which StockImport::canBeRolledBack()
     * guards.
     */
    public function rollBack(StockImport $import, User $user): StockImport
    {
        if (! $import->canBeRolledBack()) {
            throw new ImportException('This run can no longer be rolled back.');
        }

        DB::transaction(function () use ($import, $user) {
            $movements = $import->movements()
                ->with(['product', 'warehouse'])
                ->orderByDesc('id')
                ->get();

            foreach ($movements as $movement) {
                if (! $movement->product || ! $movement->warehouse) {
                    continue;
                }

                $this->inventory->setOnHand(
                    $movement->product,
                    $movement->warehouse,
                    (int) $movement->quantity_before,
                    StockMovement::REASON_IMPORT,
                    $user,
                    $import,
                    sprintf('Rolled back %s', $import->reference)
                );
            }

            $import->forceFill([
                'status' => ImportStatus::RolledBack,
                'rolled_back_at' => now(),
            ])->save();
        });

        ActivityLog::record('Stock import rolled back', $import->reference, $import);

        return $import->refresh();
    }

    /* ----------------------------------------------------------- Internals */

    private function reader(StockImport $import): SheetReader
    {
        return new SheetReader(storage_path('app/'.$import->path));
    }

    /**
     * @return array{parent_sku:?string, description:?string, shade:?string, warehouse_code:?string, quantity:?int, cost:?float, ready_on:?string}
     */
    private function extract(array $row, array $map): array
    {
        $at = function (string $field) use ($row, $map) {
            if (! isset($map[$field])) {
                return null;
            }

            $value = $row[$map[$field]] ?? null;

            return ($value === null || trim((string) $value) === '') ? null : trim((string) $value);
        };

        $quantity = $at('quantity');
        $cost = $at('cost');
        $readyOn = $at('ready_on');

        return [
            'parent_sku' => $at('parent_sku'),
            'description' => $at('description'),
            'shade' => $at('shade'),
            'warehouse_code' => $at('warehouse_code'),
            'quantity' => $quantity === null ? null : (int) preg_replace('/[^0-9\-]/', '', $quantity),
            'cost' => $cost === null ? null : (float) preg_replace('/[^0-9.\-]/', '', $cost),
            'ready_on' => $readyOn === null ? null : $this->parseDate($readyOn),
        ];
    }

    private function parseDate(string $value): ?string
    {
        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0:ImportRowStatus, 1:?string}
     */
    private function classify(array $values, ?Product $product, ?Warehouse $warehouse, ?Colourway $colourway): array
    {
        if (! $values['parent_sku']) {
            return [ImportRowStatus::Skipped, 'No mill reference on this line.'];
        }

        if (! $product) {
            return [
                ImportRowStatus::Unmatched,
                sprintf('%s is not on the catalogue — map it to an item number first.', $values['parent_sku']),
            ];
        }

        if (! $warehouse) {
            return [
                ImportRowStatus::Unmatched,
                sprintf('Warehouse "%s" is not one of ours.', $values['warehouse_code'] ?? '—'),
            ];
        }

        if ($values['quantity'] === null) {
            return [ImportRowStatus::Skipped, 'No quantity on this line.'];
        }

        if ($values['shade'] && ! $colourway) {
            return [
                ImportRowStatus::NewColourway,
                sprintf('"%s" is a colour we do not list yet. The quantity will still load.', $values['shade']),
            ];
        }

        return [ImportRowStatus::Ready, null];
    }

    /** Mills spell colours their own way, so match on either name. */
    private function matchColourway($colourways, string $shade): ?Colourway
    {
        $needle = Str::lower(trim($shade));

        return $colourways->first(fn (Colourway $c) => Str::lower($c->name) === $needle
            || Str::lower((string) $c->mill_name) === $needle
            || Str::lower($c->slug) === Str::slug($needle));
    }
}
