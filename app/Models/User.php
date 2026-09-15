<?php

namespace App\Models;

use App\Enums\CompanyUserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * One users table for everyone. Staff have no company_id; a customer login
 * always belongs to exactly one company, and inherits that company's rate
 * card. Permissions decide what a person may DO, never what they are CHARGED.
 *
 * @property-read Company|null $company
 */
class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'job_title',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------- Relations */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'placed_by_id');
    }

    public function managedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'account_manager_id');
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->whereNotNull('company_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /* ---------------------------------------------------------- Behaviour */

    public function isStaff(): bool
    {
        return $this->company_id === null;
    }

    public function isCustomer(): bool
    {
        return $this->company_id !== null;
    }

    /**
     * Where this login belongs after signing in.
     *
     * It lives on the user rather than in a route provider because it is a
     * fact about the person, not about routing — and Laravel 11 removed the
     * RouteServiceProvider that used to hold it.
     */
    public function homeUrl(): string
    {
        return $this->isStaff() ? '/admin' : '/account';
    }

    /**
     * Pricing is only released to a login whose company is approved and
     * trading. Everything that renders money asks this first.
     */
    public function canSeePricing(): bool
    {
        if ($this->isStaff()) {
            return $this->can('pricing.view');
        }

        return $this->company?->canTrade() ?? false;
    }

    public function canPlaceOrders(): bool
    {
        return $this->isCustomer()
            && $this->company?->canTrade()
            && $this->hasAnyRole([CompanyUserRole::Admin->value, CompanyUserRole::Buyer->value]);
    }

    public function companyRole(): ?CompanyUserRole
    {
        foreach (CompanyUserRole::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function firstName(): string
    {
        return Str::before($this->name, ' ');
    }
}
