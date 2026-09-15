<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('label');                    // "Warehouse — receiving dock"
            $table->string('company_name')->nullable();
            $table->string('street');
            $table->string('street_2')->nullable();
            $table->string('city');
            $table->string('state', 32)->nullable();
            $table->string('postcode', 16)->nullable();
            $table->string('country', 64)->default('United States');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_residential')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
