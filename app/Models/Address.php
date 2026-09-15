<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A ship-to address on the account's book. Editable, because addresses change.
 * Orders never point at the live record for their paperwork — they keep a
 * snapshot, so an edit here can't rewrite a packing slip issued last March.
 */
class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'label',
        'company_name',
        'street',
        'street_2',
        'city',
        'state',
        'postcode',
        'country',
        'is_default',
        'is_residential',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_residential' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeDefaultFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_default')->orderBy('label');
    }

    /** Lines for display, empty parts dropped. */
    public function lines(): array
    {
        return array_values(array_filter([
            $this->company_name,
            $this->street,
            $this->street_2,
            trim(sprintf('%s, %s %s', $this->city, $this->state, $this->postcode), ' ,'),
            $this->country,
        ]));
    }

    public function singleLine(): string
    {
        return implode(', ', $this->lines());
    }

    /**
     * The frozen copy an order carries. Anything printed on a document comes
     * from here, never from the live record.
     */
    public function snapshot(): array
    {
        return [
            'label' => $this->label,
            'company_name' => $this->company_name,
            'street' => $this->street,
            'street_2' => $this->street_2,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'is_residential' => (bool) $this->is_residential,
        ];
    }
}
