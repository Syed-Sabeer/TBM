<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the circular references the earlier migrations had to leave open.
 *
 * Two loops exist in this schema, and neither can be declared inline:
 *
 *   users.company_id            → companies    users is created first, because
 *                                              companies.account_manager_id
 *                                              points back at a staff login.
 *
 *   companies.default_warehouse_id → warehouses warehouses is created after
 *                                              companies, and a consignment
 *                                              warehouse belongs to a company.
 *
 * Both columns exist from the start; only the constraints wait until every
 * table they touch is on the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Deleting a company releases its logins rather than destroying
            // them — the orders they placed stay attributed.
            $table->foreign('company_id')
                ->references('id')->on('companies')->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('default_warehouse_id')
                ->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['default_warehouse_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
    }
};
