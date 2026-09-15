<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Order matters here.
 *
 *  1. Roles and permissions, because every user assignment needs them.
 *  2. Tiers, because a company points at one.
 *  3. The catalogue, because an override and an order line point at products.
 *  4. Accounts, which bring staff, customer logins and negotiated rates.
 *  5. Orders, which price themselves through the live pricing engine and so
 *     need everything above to already exist.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PriceTierSeeder::class,
            CatalogueSeeder::class,
            AccountSeeder::class,
            OrderSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('Demo logins (change the password before this goes anywhere real):');
        $this->command?->line('  Back office   admin@gmail.com    / '.AccountSeeder::DEMO_PASSWORD);
        $this->command?->line('  Customer      customer@gmail.com / '.AccountSeeder::DEMO_PASSWORD);
        $this->command?->newLine();
    }
}
