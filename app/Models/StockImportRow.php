<?php

namespace App\Models;

use App\Enums\ImportRowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line of a staged file, with the raw values as read and the domain
 * records they resolved to. Keeping both means a reviewer can see exactly what
 * the sheet said next to what the system made of it.
 */
class StockImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_import_id',
        'line_number',
        'parent_sku',
        'description',
        'shade',
        'warehouse_code',
        'quantity',
        'cost',
        'ready_on',
        'product_id',
        'warehouse_id',
        'colourway_id',
        'quantity_before',
        'status',
        'message',
        'raw',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportRowStatus::class,
            'raw' => 'array',
            'ready_on' => 'date',
            'cost' => 'decimal:4',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(StockImport::class, 'stock_import_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function colourway(): BelongsTo
    {
        return $this->belongsTo(Colourway::class);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', ImportRowStatus::Ready->value);
    }

    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ImportRowStatus::Unmatched->value,
            ImportRowStatus::NewColourway->value,
        ]);
    }

    public function isReady(): bool
    {
        return $this->status === ImportRowStatus::Ready;
    }

    public function delta(): ?int
    {
        if ($this->quantity === null || $this->quantity_before === null) {
            return null;
        }

        return (int) $this->quantity - (int) $this->quantity_before;
    }

    /** The customer-facing number this mill reference maps to, once resolved. */
    public function resolvedSku(): ?string
    {
        return $this->product?->sku;
    }
}
