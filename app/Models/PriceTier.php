<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceTier extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'factor', 'is_default', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'factor' => 'float',
            'is_default' => 'boolean',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /** "Tier B — Preferred" */
    public function fullName(): string
    {
        return sprintf('Tier %s — %s', $this->code, $this->name);
    }

    /** How far under the standard card this tier sits, as a percentage. */
    public function discountPercent(): float
    {
        return round((1 - $this->factor) * 100, 1);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderByDesc('factor')->first();
    }
}
