<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();          // A, B, C
            $table->string('name');                        // Distributor, Preferred, Standard
            $table->string('description')->nullable();
            // Multiplier applied to the base price. 0.86 == 14% off the standard card.
            $table->decimal('factor', 6, 4)->default(1);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
