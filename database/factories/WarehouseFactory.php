<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = \App\Models\Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->city(),
            'region' => 'West Coast',
            'type' => 'owned',
            'company_id' => null,
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postcode' => fake()->postcode(),
            'lead_time' => '1–2 business days',
            'has_decoration' => false,
            'is_active' => true,
            'include_in_storefront' => true,
            'position' => 0,
        ];
    }

    /** Holds one account's goods, so it is never offered to anyone else. */
    public function consignment($company): static
    {
        return $this->state(fn () => [
            'type' => 'consignment',
            'company_id' => $company instanceof \App\Models\Company ? $company->id : $company,
            'include_in_storefront' => false,
        ]);
    }
}
