<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A negotiated rate on one item for one account, expressed as a multiplier on
 * top of that account's tier price rather than a fixed figure — so a change to
 * the underlying rate card still flows through, and the concession stays a
 * concession.
 */
class PriceOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'factor',
        'created_by',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'factor' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** 0.92 reads as "a further 8% off". */
    public function discountPercent(): float
    {
        return round((1 - (float) $this->factor) * 100, 1);
    }

    public function label(): string
    {
        $off = $this->discountPercent();

        return $off > 0
            ? sprintf('%s%% below tier', rtrim(rtrim(number_format($off, 1), '0'), '.'))
            : sprintf('%s%% above tier', rtrim(rtrim(number_format(abs($off), 1), '0'), '.'));
    }
}
