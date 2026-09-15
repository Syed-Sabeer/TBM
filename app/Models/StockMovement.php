<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An append-only record of every change to a stock figure. Nothing in the
 * application updates a movement once written; a correction is another
 * movement. This is what makes "who changed this number and why" answerable.
 */
class StockMovement extends Model
{
    use HasFactory;

    public const REASON_IMPORT = 'import';
    public const REASON_CYCLE_COUNT = 'cycle_count';
    public const REASON_DAMAGE = 'damage';
    public const REASON_TRANSFER = 'transfer';
    public const REASON_ORDER = 'order';
    public const REASON_RETURN = 'return';
    public const REASON_RECEIPT = 'receipt';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'user_id',
        'quantity_before',
        'quantity_after',
        'delta',
        'reason',
        'source_type',
        'source_id',
        'note',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at');
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            self::REASON_IMPORT => 'Morning import',
            self::REASON_CYCLE_COUNT => 'Cycle count',
            self::REASON_DAMAGE => 'Damage write-off',
            self::REASON_TRANSFER => 'Warehouse transfer',
            self::REASON_ORDER => 'Allocated to an order',
            self::REASON_RETURN => 'Customer return',
            self::REASON_RECEIPT => 'Goods received',
            default => ucfirst(str_replace('_', ' ', (string) $this->reason)),
        };
    }

    public function isIncrease(): bool
    {
        return $this->delta > 0;
    }

    /** "+1,250" / "−80", for the movement log. */
    public function signedDelta(): string
    {
        return ($this->delta > 0 ? '+' : ($this->delta < 0 ? '−' : ''))
            .number_format(abs((int) $this->delta));
    }
}
