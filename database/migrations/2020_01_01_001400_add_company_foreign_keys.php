<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * companies.default_warehouse_id is declared without a constraint in the
 * companies migration because warehouses is created afterwards. This closes
 * the loop once both tables exist.
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }
};
