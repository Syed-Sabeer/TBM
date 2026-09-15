<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\Company;
use App\Models\PriceOverride;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Staff and customer accounts from database/data/accounts.php.
 *
 * The two demo logins the brief asked for are in there: admin@gmail.com for
 * the back office and customer@gmail.com for the portal. Both, and everyone
 * else, get the same password — see DEMO_PASSWORD, and change it before this
 * goes anywhere near a real server.
 */
class AccountSeeder extends Seeder
{
    /**
     * Demo credentials only. Production seeding must not use this: either
     * remove the seeder from production or generate and mail a password.
     */
    public const DEMO_PASSWORD = 'adminadmin';

    public function run(): void
    {
        $data = require database_path('data/accounts.php');

        $this->staff($data['staff']);
        $this->companies($data['companies']);
    }

    private function staff(array $rows): void
    {
        foreach ($rows as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'company_id' => null,          // staff have no company
                    'name' => $row['name'],
                    'job_title' => $row['job_title'],
                    'password' => self::DEMO_PASSWORD,
                    'is_active' => true,
                    'last_seen_at' => now()->subHours(random_int(1, 40)),
                ]
            );

            $user->syncRoles([$row['role']]);
        }

        $this->command?->info(sprintf('  %d staff logins', count($rows)));
    }

    private function companies(array $rows): void
    {
        $tiers = PriceTier::pluck('id', 'code');
        $managers = User::staff()->pluck('id', 'name');
        $products = Product::pluck('id', 'sku');

        foreach ($rows as $row) {
            $company = Company::updateOrCreate(
                ['account_number' => $row['account_number']],
                [
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'business_type' => $row['business_type'],
                    'price_tier_id' => $tiers[$row['tier']] ?? null,
                    'account_manager_id' => $managers[$row['account_manager']] ?? null,
                    'status' => $row['status'],
                    'payment_terms' => $row['payment_terms'],
                    'credit_limit' => $row['credit_limit'],
                    'credit_used' => $row['credit_used'],
                    'certificate_status' => $row['certificate_status'],
                    'certificate_expires_at' => $row['certificate_expires_at']
                        ? Carbon::parse($row['certificate_expires_at'])
                        : null,
                    'billing_street' => $row['billing_street'],
                    'billing_city' => $row['billing_city'],
                    'billing_state' => $row['billing_state'],
                    'billing_postcode' => $row['billing_postcode'],
                    'billing_country' => 'United States',
                    'customer_since' => Carbon::parse($row['customer_since']),
                ]
            );

            $this->addresses($company, $row);
            $this->users($company, $row['users']);
            $this->overrides($company, $row['overrides'], $products);
        }

        $this->command?->info(sprintf(
            '  %d accounts (%d awaiting approval)',
            count($rows),
            collect($rows)->where('status', AccountStatus::PendingApproval->value)->count()
        ));
    }

    private function addresses(Company $company, array $row): void
    {
        $company->addresses()->updateOrCreate(
            ['label' => 'Receiving dock'],
            [
                'company_name' => $company->name,
                'street' => $row['billing_street'],
                'city' => $row['billing_city'],
                'state' => $row['billing_state'],
                'postcode' => $row['billing_postcode'],
                'country' => 'United States',
                'is_default' => true,
                'notes' => 'Deliveries 8am–3pm, call the dock on arrival.',
            ]
        );
    }

    private function users(Company $company, array $users): void
    {
        foreach ($users as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'company_id' => $company->id,
                    'name' => $row['name'],
                    'job_title' => $row['job_title'],
                    'password' => self::DEMO_PASSWORD,
                    'is_active' => true,
                    'last_seen_at' => now()->subDays(random_int(0, 20)),
                ]
            );

            $user->syncRoles([$row['role']]);
        }
    }

    /**
     * The negotiated rates. These are what make two accounts pay different
     * numbers for the same item at the same quantity — the thing the whole
     * pricing design exists for.
     */
    private function overrides(Company $company, array $overrides, $products): void
    {
        foreach ($overrides as $sku => $factor) {
            if (! isset($products[$sku])) {
                continue;
            }

            PriceOverride::updateOrCreate(
                ['company_id' => $company->id, 'product_id' => $products[$sku]],
                [
                    'factor' => $factor,
                    'note' => 'Volume commitment agreed at renewal.',
                ]
            );
        }
    }
}
