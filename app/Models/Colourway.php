<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Colourway extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'mill_name', 'hex_body', 'hex_dark', 'hex_light', 'hex_cord', 'position',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot(['is_active', 'position']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** True when the body colour is dark enough to need light ink on top. */
    public function isDark(): bool
    {
        [$r, $g, $b] = sscanf($this->hex_body, '#%02x%02x%02x');

        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000 < 140;
    }
}
