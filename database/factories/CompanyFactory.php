<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\PriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = \App\Models\Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'account_number' => 'TB-'.fake()->unique()->numberBetween(10000, 99999),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'business_type' => 'Promotional products distributor',
            'price_tier_id' => PriceTier::factory(),
            'status' => AccountStatus::Active,
            'payment_terms' => 'Net 30',
            'credit_limit' => fake()->numberBetween(10, 150) * 1000,
            'credit_used' => 0,
            'certificate_status' => 'verified',
            'certificate_expires_at' => now()->addYear(),
            'billing_street' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => fake()->stateAbbr(),
            'billing_postcode' => fake()->postcode(),
            'billing_country' => 'United States',
            'customer_since' => now()->subYears(2),
        ];
    }

    /** Cannot see a price until someone approves it. */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::PendingApproval,
            'certificate_status' => 'unverified',
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => AccountStatus::OnHold]);
    }
}
