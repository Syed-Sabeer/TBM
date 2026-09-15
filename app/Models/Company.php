<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The customer account. One company, one rate card, many logins, one shared
 * order history — the shape the business actually sells in.
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'name',
        'trading_name',
        'slug',
        'website',
        'business_type',
        'price_tier_id',
        'account_manager_id',
        'status',
        'payment_terms',
        'credit_limit',
        'credit_used',
        'ein',
        'resale_certificate',
        'certificate_status',
        'certificate_expires_at',
        'billing_street',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'default_warehouse_id',
        'customer_since',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'credit_limit' => 'decimal:2',
            'credit_used' => 'decimal:2',
            'certificate_expires_at' => 'date',
        ];
    }

    /* ---------------------------------------------------------- Relations */

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(PriceOverride::class);
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::Active->value);
    }

    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::PendingApproval->value);
    }

    public function scopeTrading(Builder $query): Builder
    {
        return $query->whereIn('status', [AccountStatus::Active->value]);
    }

    /* ---------------------------------------------------------- Behaviour */

    public function canTrade(): bool
    {
        return $this->status->canTrade() && $this->hasValidCertificate();
    }

    public function hasValidCertificate(): bool
    {
        if ($this->certificate_status !== 'verified') {
            return false;
        }

        return $this->certificate_expires_at === null
            || $this->certificate_expires_at->isFuture();
    }

    public function creditAvailable(): float
    {
        return max(0, (float) $this->credit_limit - (float) $this->credit_used);
    }

    public function creditUsedPercent(): float
    {
        if ((float) $this->credit_limit <= 0) {
            return 0;
        }

        return min(100, (float) $this->credit_used / (float) $this->credit_limit * 100);
    }

    public function overrideFactorFor(Product $product): float
    {
        return (float) ($this->priceOverrides
            ->firstWhere('product_id', $product->id)?->factor ?? 1.0);
    }

    public function billingAddressLines(): array
    {
        return array_values(array_filter([
            $this->billing_street,
            trim(sprintf('%s, %s %s', $this->billing_city, $this->billing_state, $this->billing_postcode), ' ,'),
            $this->billing_country,
        ]));
    }
}
