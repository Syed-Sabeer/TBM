<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\CompanyUserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\PriceOverride;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\PricingService;
use App\Services\Reporting\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer accounts — and, in practice, the rate card, because "different
 * rates for different customers on the same product" is settled here: the
 * tier for the account, and an override factor for individual items.
 */
class CompanyController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->with(['tier', 'accountManager'])
            ->withCount('users')
            ->withSum(['orders as spend' => fn ($q) => $q->revenue()], 'merchandise_total')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('tier'), fn ($q) => $q->where('price_tier_id', $request->input('tier')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->input('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('account_number', 'like', $like)
                    ->orWhere('trading_name', 'like', $like));
            })
            ->orderByDesc('spend')
            ->paginate(25)
            ->withQueryString();

        return view('admin.companies.index', [
            'companies' => $companies,
            'tiers' => PriceTier::orderBy('position')->get(),
            'statuses' => AccountStatus::options(),
            'filters' => $request->only(['status', 'tier', 'q']),
        ]);
    }

    public function show(Request $request, Company $company): View
    {
        $this->authorize('view', $company);

        $company->load(['tier', 'accountManager', 'users.roles', 'addresses', 'priceOverrides.product']);

        return view('admin.companies.show', [
            'company' => $company,
            'headline' => $this->reports->headline($company, 12),
            'byMonth' => $this->reports->byMonth($company, 12),
            'byItem' => $this->reports->byItem($company, now()->subYear(), 10),
            'byUser' => $this->reports->byUser($company, now()->subYear()),
            'orders' => $company->orders()->with('placedBy')->latestFirst()->limit(10)->get(),
            'tiers' => PriceTier::orderBy('position')->get(),
            'managers' => User::staff()->active()->orderBy('name')->get(),
            'roles' => CompanyUserRole::cases(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'trading_name' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:160'],
            'account_manager_id' => ['nullable', 'exists:users,id'],
            'payment_terms' => ['nullable', 'string', 'max:32'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'ein' => ['nullable', 'string', 'max:32'],
            'resale_certificate' => ['nullable', 'string', 'max:64'],
            'certificate_status' => ['nullable', Rule::in(['unverified', 'verified', 'expired'])],
            'certificate_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $company->update($data);

        ActivityLog::record('Account updated', $company->name, $company);

        return back()->with('status', 'Account saved.');
    }

    /**
     * Approving an account is the moment pricing becomes visible to it, which
     * is why it is its own action with its own permission rather than a field
     * on the edit form.
     */
    public function approve(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('approve', $company);

        $data = $request->validate([
            'price_tier_id' => ['required', 'exists:price_tiers,id'],
            'payment_terms' => ['required', 'string', 'max:32'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'account_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $company->update($data + [
            'status' => AccountStatus::Active,
            'certificate_status' => 'verified',
            'customer_since' => $company->customer_since ?? now(),
        ]);

        ActivityLog::record(
            'Account approved',
            sprintf('%s on %s, terms %s', $company->name, $company->tier?->fullName(), $company->payment_terms),
            $company
        );

        return back()->with('status', sprintf(
            '%s is live. Pricing is now visible to all %s of their logins.',
            $company->name,
            $company->users()->count()
        ));
    }

    public function hold(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(AccountStatus::cases(), 'value'))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $company->update(['status' => $data['status']]);

        ActivityLog::record(
            'Account status changed',
            sprintf('%s → %s. %s', $company->name, $company->status->label(), $data['reason'] ?? ''),
            $company
        );

        return back()->with('status', sprintf('%s is now %s.', $company->name, strtolower($company->status->label())));
    }

    /* --------------------------------------------------------- Rate card */

    public function setTier(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('setRates', $company);

        $data = $request->validate(['price_tier_id' => ['required', 'exists:price_tiers,id']]);

        $was = $company->tier?->fullName();
        $company->update($data);
        $company->refresh()->load('tier');

        ActivityLog::record(
            'Rate card changed',
            sprintf('%s moved from %s to %s', $company->name, $was, $company->tier?->fullName()),
            $company
        );

        return back()->with('status', sprintf(
            'Every price %s sees is now on %s. Orders already placed are unchanged.',
            $company->name,
            $company->tier?->fullName()
        ));
    }

    /**
     * A negotiated rate on one item. Stored as a factor rather than a fixed
     * price so a later change to the underlying card still flows through.
     */
    public function storeOverride(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('setRates', $company);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'discount_percent' => ['required', 'numeric', 'min:-50', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $factor = round(1 - ((float) $data['discount_percent'] / 100), 4);

        $override = PriceOverride::updateOrCreate(
            ['company_id' => $company->id, 'product_id' => $product->id],
            ['factor' => $factor, 'created_by' => $request->user()->id, 'note' => $data['note'] ?? null],
        );

        // Show what it actually does to the number, not just the factor.
        $company->load('priceOverrides');
        $quote = $this->pricing->quote($product, $company->fresh()->load('tier'), $product->moq);

        ActivityLog::record(
            'Negotiated rate set',
            sprintf('%s on %s — %s', $company->name, $product->sku, $override->label()),
            $company
        );

        return back()->with('status', sprintf(
            '%s now pays %s a piece on %s at %s.',
            $company->name,
            \App\Support\Money::unit($quote->unitPrice),
            $product->sku,
            number_format($product->moq)
        ));
    }

    public function destroyOverride(Company $company, PriceOverride $override): RedirectResponse
    {
        $this->authorize('setRates', $company);

        abort_unless($override->company_id === $company->id, 404);

        $sku = $override->product->sku;
        $override->delete();

        ActivityLog::record('Negotiated rate removed', sprintf('%s on %s', $company->name, $sku), $company);

        return back()->with('status', sprintf('%s is back on standard tier pricing for %s.', $company->name, $sku));
    }

    /* ------------------------------------------------------------- Users */

    public function storeUser(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('inviteUsers', $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'job_title' => ['nullable', 'string', 'max:80'],
            'role' => ['required', Rule::in(array_column(CompanyUserRole::cases(), 'value'))],
            'password' => ['required', Password::defaults()->min(10)],
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'job_title' => $data['job_title'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        ActivityLog::record('Account login created', sprintf('%s on %s', $user->email, $company->name), $user);

        return back()->with('status', sprintf('%s added to %s.', $user->name, $company->name));
    }
}
