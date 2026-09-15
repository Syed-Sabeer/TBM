<?php

namespace Database\Seeders;

use App\Models\PriceTier;
use Illuminate\Database\Seeder;

/**
 * Three tiers, and the factor is the whole of the mechanism: Tier A pays 86%
 * of the standard card on everything, Tier B 94%, Tier C the card itself.
 *
 * Tier C is the default because a new account should never open on a better
 * rate than an established one by accident.
 */
class PriceTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['code' => 'A', 'name' => 'Distributor', 'factor' => 0.86, 'is_default' => false, 'position' => 1,
             'description' => 'Container-scale programmes and national distributors.'],
            ['code' => 'B', 'name' => 'Preferred', 'factor' => 0.94, 'is_default' => false, 'position' => 2,
             'description' => 'Established accounts buying in depth through the year.'],
            ['code' => 'C', 'name' => 'Standard', 'factor' => 1.00, 'is_default' => true, 'position' => 3,
             'description' => 'New and occasional trade accounts.'],
        ];

        foreach ($tiers as $tier) {
            PriceTier::updateOrCreate(['code' => $tier['code']], $tier);
        }
    }
}
