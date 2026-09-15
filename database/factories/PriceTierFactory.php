<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PriceTier>
 */
class PriceTierFactory extends Factory
{
    protected $model = \App\Models\PriceTier::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomLetter(),
            'name' => 'Standard',
            'description' => null,
            'factor' => 1.00,
            'is_default' => false,
            'position' => 0,
        ];
    }
}
