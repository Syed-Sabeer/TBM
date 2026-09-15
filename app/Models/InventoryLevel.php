<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product in one warehouse. The morning mill sheet writes `on_hand`;
 * everything else is domestic bookkeeping.
 */
class InventoryLevel extends Model
{
    use HasFactory;

    protected $table = 'inventory_levels';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'on_hand',
        'allocated',
        'on_order',
        'next_intake_on',
        'synced_at',
    ];

    protected $casts = [
        'next_intake_on' => 'date',
        'synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * What a customer may actually buy: on hand less what is already spoken
     * for by confirmed orders.
     */
    public function available(): int
    {
        return max(0, (int) $this->on_hand - (int) $this->allocated);
    }

    public function isStale(): bool
    {
        return $this->synced_at === null
            || $this->synced_at->lt(now()->subDay());
    }
}
