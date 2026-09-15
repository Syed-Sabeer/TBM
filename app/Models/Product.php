<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sellable item.
 *
 * The two identifiers are the whole point of this model. `parent_sku` is the
 * mill reference and belongs on purchase orders and pick lists; `sku` is the
 * customer-facing item number and is the only one allowed to appear on the
 * storefront, the packing slip or the invoice. Nothing here exposes the mill
 * reference by accident: `customerNumber()` is what views should call.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_sku',
        'sku',
        'category_id',
        'name',
        'slug',
        'description',
        'shape',
        'material',
        'fabric_weight',
        'sizes',
        'flags',
        'base_price',
        'cost_price',
        'moq',
        'carton_quantity',
        'order_step',
        'origin',
        'is_published',
        'position',
    ];

    protected $casts = [
        'sizes' => 'array',
        'flags' => 'array',
        'base_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'is_published' => 'boolean',
    ];

    /*
     | The mill reference must never be serialised into a customer-facing
     | payload by accident, so it is hidden by default. Back-office code that
     | genuinely needs it reads $product->parent_sku directly, or calls
     | millReference() below, which reads as deliberate.
     */
    protected $hidden = ['parent_sku', 'cost_price'];

    /* ---------------------------------------------------------- Relations */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function colourways(): BelongsToMany
    {
        return $this->belongsToMany(Colourway::class)
            ->withPivot(['is_active', 'position'])
            ->orderByPivot('position');
    }

    public function activeColourways(): BelongsToMany
    {
        return $this->colourways()->wherePivot('is_active', true);
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(PriceOverride::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    /**
     * One search box over both identifiers and the name. Staff type mill
     * references into it; customers only ever type their own item number.
     */
    public function scopeSearch(Builder $query, ?string $term, bool $includeMillReference = false): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $includeMillReference) {
            $like = '%'.$term.'%';
            $q->where('sku', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('material', 'like', $like);

            if ($includeMillReference) {
                $q->orWhere('parent_sku', 'like', $like);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ------------------------------------------------------- Identifiers */

    /** The only identifier a customer may ever see. */
    public function customerNumber(): string
    {
        return $this->sku;
    }

    /** The mill reference. Purchase orders and pick lists only. */
    public function millReference(): string
    {
        return $this->parent_sku;
    }

    /* ------------------------------------------------------------- Stock */

    public function stockAt(Warehouse|int|string $warehouse): int
    {
        $id = $warehouse instanceof Warehouse ? $warehouse->id : $warehouse;

        return (int) ($this->inventoryLevels->firstWhere('warehouse_id', $id)?->available() ?? 0);
    }

    public function totalStock(): int
    {
        return (int) $this->inventoryLevels->sum(fn (InventoryLevel $level) => $level->available());
    }

    public function onOrder(): int
    {
        return (int) $this->inventoryLevels->sum('on_order');
    }

    public function isInStock(): bool
    {
        return $this->totalStock() > 0;
    }

    /**
     * Stock keyed by warehouse code, for the item page strip. Only warehouses
     * flagged for the storefront are included — a consignment site holding one
     * account's goods is nobody else's business.
     */
    public function stockByWarehouse(): array
    {
        return $this->inventoryLevels
            ->filter(fn (InventoryLevel $l) => $l->warehouse?->include_in_storefront)
            ->mapWithKeys(fn (InventoryLevel $l) => [$l->warehouse->code => $l->available()])
            ->all();
    }

    /* ------------------------------------------------------------- Money */

    /**
     * FOB cost. Falls back to a configured ratio of the base price when the
     * mill sheet has not carried a cost for this item yet, so margin reporting
     * still has something to work with.
     */
    public function effectiveCost(): float
    {
        return (float) ($this->cost_price ?: $this->base_price * config('tbm.cost_ratio'));
    }

    /* ------------------------------------------------------------- Traits */

    public function sizeLabels(): array
    {
        return array_map(
            fn ($size) => is_array($size) ? ($size['label'] ?? '') : (string) $size,
            $this->sizes ?? []
        );
    }

    public function defaultSize(): string
    {
        return $this->sizeLabels()[0] ?? '';
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }
}
