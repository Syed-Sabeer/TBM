<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'region',
        'type',
        'company_id',
        'street',
        'city',
        'state',
        'postcode',
        'lead_time',
        'floor_space',
        'has_decoration',
        'is_active',
        'include_in_storefront',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_decoration' => 'boolean',
            'is_active' => 'boolean',
            'include_in_storefront' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------- Relations */

    /** Set only on a consignment site, which holds stock for one account. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOnStorefront(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('include_in_storefront', true)
            ->orderBy('position');
    }

    /** Sites a given account is allowed to ship from. */
    public function scopeAvailableTo(Builder $query, ?Company $company): Builder
    {
        return $query->active()->where(function (Builder $q) use ($company) {
            $q->whereNull('company_id');

            if ($company) {
                $q->orWhere('company_id', $company->id);
            }
        })->orderBy('position');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /* ---------------------------------------------------------- Behaviour */

    public function isConsignment(): bool
    {
        return $this->company_id !== null;
    }

    public function locationLine(): string
    {
        return trim(implode(', ', array_filter([$this->city, $this->state])), ', ');
    }

    public function unitsOnHand(): int
    {
        return (int) $this->inventoryLevels()->sum('on_hand');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            '3pl' => 'Third-party logistics',
            'consignment' => 'Consignment',
            default => 'Owned',
        };
    }
}
