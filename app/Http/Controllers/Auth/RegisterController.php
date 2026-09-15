<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\CompanyUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\PriceTier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * A wholesale application creates a company and its first login together.
 *
 * The account opens as pending, on the default tier, with no certificate
 * verified — so the applicant can sign in and look around the catalogue, and
 * cannot see a single price until a human approves them. That is the whole
 * point of the gate, and it is enforced by the account's status rather than by
 * anything on this page.
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register', [
            'businessTypes' => [
                'Promotional products distributor',
                'Retailer',
                'Grocery or convenience',
                'Screen printer or decorator',
                'Event or agency',
                'Non-profit',
                'Other',
            ],
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $company = Company::create([
                'account_number' => $this->nextAccountNumber(),
                'name' => $request->string('company_name'),
                'trading_name' => $request->input('trading_name'),
                'slug' => $this->uniqueSlug($request->string('company_name')),
                'website' => $request->input('website'),
                'business_type' => $request->input('business_type'),
                'price_tier_id' => PriceTier::default()?->id,
                'status' => AccountStatus::PendingApproval,
                'payment_terms' => 'Prepay',
                'credit_limit' => 0,
                'ein' => $request->input('ein'),
                'resale_certificate' => $request->input('resale_certificate'),
                'certificate_status' => 'unverified',
                'billing_street' => $request->input('billing_street'),
                'billing_city' => $request->input('billing_city'),
                'billing_state' => $request->input('billing_state'),
                'billing_postcode' => $request->input('billing_postcode'),
                'customer_since' => now(),
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $request->string('name'),
                'email' => $request->string('email')->lower(),
                'job_title' => $request->input('job_title'),
                'phone' => $request->input('phone'),
                'password' => $request->string('password'),
                'is_active' => true,
            ]);

            // The applicant runs the account, so they can add colleagues.
            $user->assignRole(CompanyUserRole::Admin->value);

            $company->addresses()->create([
                'label' => 'Billing address',
                'company_name' => $company->name,
                'street' => $request->input('billing_street'),
                'city' => $request->input('billing_city'),
                'state' => $request->input('billing_state'),
                'postcode' => $request->input('billing_postcode'),
                'is_default' => true,
            ]);

            ActivityLog::record(
                'Wholesale application received',
                sprintf('%s — %s', $company->name, $user->email),
                $company
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.dashboard')
            ->with('status', 'Application received. Pricing opens up as soon as an account manager has reviewed it — usually within one business day.');
    }

    private function nextAccountNumber(): string
    {
        $last = Company::orderByDesc('id')->value('account_number');
        $sequence = $last ? ((int) filter_var($last, FILTER_SANITIZE_NUMBER_INT)) + 1 : 10_001;

        return 'TB-'.$sequence;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'account';
        $slug = $base;
        $n = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
