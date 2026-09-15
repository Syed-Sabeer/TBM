<?php

namespace Database\Seeders;

use App\Enums\CompanyUserRole;
use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and permissions.
 *
 * Two families in one table, kept apart by naming. Staff roles are job titles
 * in the back office; customer roles describe what a login may do inside its
 * own account. A customer role carries no permissions at all — what a customer
 * may do is decided by the policies, against their own company. Permissions
 * here govern the back office only, so there is no path by which granting a
 * staff permission widens what a customer can reach.
 *
 * Note what is NOT here: nothing about price level. What an account is charged
 * comes from its tier and its negotiated rates, never from a role. Promoting
 * someone from Buyer to Admin does not change a single figure they see.
 */
class RoleSeeder extends Seeder
{
    /** Back-office permissions, grouped by the thing they govern. */
    public const PERMISSIONS = [
        'orders.view' => 'See orders across all accounts',
        'orders.manage' => 'Confirm, ship and cancel orders',

        'companies.view' => 'See customer accounts',
        'companies.manage' => 'Edit account details, credit and users',
        'companies.approve' => 'Approve an application, releasing pricing to it',

        'catalogue.manage' => 'Add and edit products, colours and categories',

        'inventory.view' => 'See stock figures and movements',
        'inventory.manage' => 'Correct stock and move it between warehouses',

        'imports.run' => 'Upload and preview a sheet',
        'imports.apply' => 'Write a previewed sheet to live stock',

        'pricing.view' => 'See prices in the back office',
        'pricing.manage' => 'Change tiers and negotiated rates',

        'reports.view' => 'See sales reporting',
        'reports.margin' => 'See cost and margin',

        'users.manage' => 'Add and edit staff logins',
        'activity.view' => 'Read the activity log',
    ];

    /**
     * Who gets what. Owner is absent deliberately — it is granted everything
     * through a Gate::before in AuthServiceProvider, so a permission added
     * later never has to be remembered here.
     */
    public const STAFF_MATRIX = [
        StaffRole::AccountManager->value => [
            'orders.view', 'orders.manage',
            'companies.view', 'companies.manage', 'companies.approve',
            'inventory.view',
            'pricing.view', 'pricing.manage',
            'reports.view', 'reports.margin',
            'catalogue.manage',
            'activity.view',
        ],

        StaffRole::Inventory->value => [
            'orders.view',
            'companies.view',
            'catalogue.manage',
            'inventory.view', 'inventory.manage',
            'imports.run', 'imports.apply',
            'pricing.view',
            'reports.view',
        ],

        // Customer Care answers the phone: it needs to see everything a
        // customer might ask about, and change almost nothing.
        StaffRole::CustomerCare->value => [
            'orders.view', 'orders.manage',
            'companies.view',
            'inventory.view',
            'pricing.view',
            'reports.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Owner: every permission, now and in future.
        Role::findOrCreate(StaffRole::Owner->value, 'web')
            ->syncPermissions(Permission::all());

        foreach (self::STAFF_MATRIX as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        // Customer roles hold no back-office permissions by design.
        foreach (CompanyUserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web')->syncPermissions([]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
