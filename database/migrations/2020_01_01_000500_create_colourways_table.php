<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colourways', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();            // natural, forest
            $table->string('name');                       // Natural, Forest
            $table->string('mill_name')->nullable();      // NATURAL, FOREST GRN
            $table->string('hex_body', 7);
            $table->string('hex_dark', 7);
            $table->string('hex_light', 7);
            $table->string('hex_cord', 7);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colourways');
    }
};
