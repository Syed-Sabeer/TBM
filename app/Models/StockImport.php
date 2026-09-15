<?php

namespace App\Models;

use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * One run of the morning sheet, or of any other file the business loads.
 *
 * An import is staged and previewed before it touches a live figure: rows are
 * parsed and resolved first, and only an explicit apply writes stock. That is
 * what makes a bad column map recoverable, and it is why every applied row
 * leaves a StockMovement behind carrying the before value.
 */
class StockImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'type',
        'status',
        'filename',
        'path',
        'user_id',
        'company_id',
        'column_map',
        'rows_read',
        'rows_updated',
        'rows_added',
        'rows_skipped',
        'warnings',
        'was_scheduled',
        'applied_at',
        'rolled_back_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'column_map' => 'array',
            'was_scheduled' => 'boolean',
            'applied_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    /* ---------------------------------------------------------- Relations */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Set only on a rate-card import, which loads against one account. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(StockImportRow::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeApplied(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ImportStatus::Applied->value,
            ImportStatus::AppliedWithWarnings->value,
        ]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /* ---------------------------------------------------------- Behaviour */

    public function isApplied(): bool
    {
        return in_array($this->status, [
            ImportStatus::Applied,
            ImportStatus::AppliedWithWarnings,
        ], true);
    }

    public function canBeApplied(): bool
    {
        return in_array($this->status, [ImportStatus::Draft, ImportStatus::Previewed], true)
            && $this->rows()->where('status', ImportRowStatus::Ready->value)->exists();
    }

    /**
     * Reversible only while it is the most recent applied run against the same
     * items — after a later import the before values no longer describe now.
     */
    public function canBeRolledBack(): bool
    {
        return $this->isApplied()
            && $this->rolled_back_at === null
            && $this->applied_at?->gt(now()->subDays(7));
    }

    public function rowsNeedingAttention(): int
    {
        return $this->rows()
            ->whereIn('status', [
                ImportRowStatus::Unmatched->value,
                ImportRowStatus::NewColourway->value,
            ])
            ->count();
    }

    public function summaryLine(): string
    {
        return sprintf(
            '%s read · %s updated · %s added · %s skipped',
            number_format($this->rows_read),
            number_format($this->rows_updated),
            number_format($this->rows_added),
            number_format($this->rows_skipped)
        );
    }

    public function sourceLabel(): string
    {
        return $this->was_scheduled ? 'Scheduled' : ($this->user?->name ?? 'Manual');
    }

    public static function nextReference(): string
    {
        return sprintf('IMP-%s-%04d', now()->format('Ymd'), random_int(1000, 9999));
    }
}
