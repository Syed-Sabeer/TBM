<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();            // LAX, EWR, DFW
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('type', 32)->default('owned');    // owned | 3pl | consignment
            // A consignment site holds stock for one account only.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 32)->nullable();
            $table->string('postcode', 16)->nullable();
            $table->string('lead_time')->nullable();         // "1–2 business days"
            $table->string('floor_space')->nullable();
            $table->boolean('has_decoration')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_storefront')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
