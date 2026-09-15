<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line on an order.
 *
 * The descriptive fields are copied here rather than read through the product
 * relation on purpose: rename an item, retire a colour or re-source it from a
 * different mill, and every historical document still reprints exactly as it
 * was issued. The product relation exists for reporting, not for paperwork.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'colourway_id',
        'sku',
        'parent_sku',
        'name',
        'colour_name',
        'size',
        'decoration',
        'quantity',
        'unit_price',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    /** As with Product, the mill reference does not leak into serialisation. */
    protected $hidden = ['parent_sku', 'unit_cost'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function colourway(): BelongsTo
    {
        return $this->belongsTo(Colourway::class);
    }

    /** Printed on the invoice and the packing slip. */
    public function customerNumber(): string
    {
        return $this->sku;
    }

    /** Printed on the purchase order and the pick list. Never on either above. */
    public function millReference(): string
    {
        return $this->parent_sku;
    }

    public function isDecorated(): bool
    {
        return $this->decoration !== '' && ! str_starts_with($this->decoration, 'Blank');
    }

    public function description(): string
    {
        return trim(implode(' · ', array_filter([
            $this->name,
            $this->colour_name,
            $this->size,
        ])));
    }

    public function lineCost(): float
    {
        return (float) ($this->unit_cost ?? 0) * (int) $this->quantity;
    }
}
