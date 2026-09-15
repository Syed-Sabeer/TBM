<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Who did what, in plain language. Written by the services that change
 * something worth explaining later — a rate card, an account status, a stock
 * figure — and read by the back office. Append-only.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'action',
        'detail',
        'subject_type',
        'subject_id',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeFor(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * The one way anything in the app writes to this table.
     */
    public static function record(string $action, ?string $detail = null, ?Model $subject = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'detail' => $detail,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
        ]);
    }

    public function actorName(): string
    {
        return $this->user?->name ?? 'System';
    }
}
