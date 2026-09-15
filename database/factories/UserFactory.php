<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'company_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'job_title' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /** A customer login on a given account. */
    public function forCompany(Company|callable $company = null): static
    {
        return $this->state(fn () => [
            'company_id' => $company instanceof Company ? $company->id : Company::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
