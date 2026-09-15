<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An order belongs to the company, not to the person who placed it. Every
 * login on the account sees it, which is the behaviour the business asked for.
 *
 * Money and the ship-to address are captured at the moment of placement. A
 * later change to a rate card or an address book entry must not rewrite what
 * was agreed.
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'company_id',
        'placed_by_id',
        'warehouse_id',
        'address_id',
        'status',
        'customer_po',
        'job_reference',
        'shipping_service',
        'payment_terms',
        'in_hands_on',
        'merchandise_total',
        'decoration_total',
        'freight_total',
        'tax_total',
        'grand_total',
        'total_pieces',
        'ship_to',
        'customer_notes',
        'placed_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'ship_to' => 'array',
            'in_hands_on' => 'date',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'merchandise_total' => 'decimal:2',
            'decoration_total' => 'decimal:2',
            'freight_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    /* ---------------------------------------------------------- Relations */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function customerNotes(): HasMany
    {
        return $this->notes()->where('is_internal', false);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        return $query->where('company_id', $company instanceof Company ? $company->id : $company);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::PendingConfirmation->value,
            OrderStatus::Confirmed->value,
            OrderStatus::InProduction->value,
            OrderStatus::Shipped->value,
        ]);
    }

    public function scopeRevenue(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled->value);
    }

    public function scopePlacedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('placed_at', [$from, $to]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('placed_at')->orderByDesc('id');
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /* ---------------------------------------------------------- Behaviour */

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /** A customer may still pull an order back before it is confirmed. */
    public function isCancellable(): bool
    {
        return $this->status === OrderStatus::PendingConfirmation;
    }

    public function shipToLines(): array
    {
        $s = $this->ship_to ?? [];

        return array_values(array_filter([
            $s['company_name'] ?? null,
            $s['street'] ?? null,
            $s['street_2'] ?? null,
            trim(sprintf('%s, %s %s', $s['city'] ?? '', $s['state'] ?? '', $s['postcode'] ?? ''), ' ,'),
            $s['country'] ?? null,
        ]));
    }

    public function lineCount(): int
    {
        return $this->items->count();
    }

    /** Margin on the order, as booked. Back-office reporting only. */
    public function costTotal(): float
    {
        return (float) $this->items->sum(
            fn (OrderItem $item) => (float) ($item->unit_cost ?? 0) * $item->quantity
        );
    }

    public function marginPercent(): float
    {
        $revenue = (float) $this->merchandise_total;

        if ($revenue <= 0) {
            return 0;
        }

        return round(($revenue - $this->costTotal()) / $revenue * 100, 1);
    }

    /**
     * Reference numbers are readable by design: staff and customers quote them
     * over the phone.
     */
    public static function nextReference(): string
    {
        $year = now()->format('Y');
        $last = static::where('reference', 'like', "TBM-{$year}-%")
            ->orderByDesc('id')
            ->value('reference');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1001;

        return sprintf('TBM-%s-%04d', $year, $sequence);
    }
}
