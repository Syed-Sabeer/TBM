<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /*
             | Two identifiers, deliberately separate.
             |  parent_sku  the mill reference. Purchase orders and pick lists
             |              only. It must never reach a customer.
             |  sku         the customer-facing item number, printed on the
             |              site, the packing slip and the invoice.
             | Re-source an item from a different mill and only parent_sku moves.
             */
            $table->string('parent_sku', 32)->unique();
            $table->string('sku', 32)->unique();

            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('shape', 32)->default('tote');
            $table->string('material');
            $table->string('fabric_weight', 32)->nullable();
            $table->json('sizes');
            $table->json('flags')->nullable();

            // Tier C price at the smallest quantity break. Every other price
            // in the system is derived from this.
            $table->decimal('base_price', 10, 4);
            // Last imported FOB cost. Margin reporting only.
            $table->decimal('cost_price', 10, 4)->nullable();

            $table->unsignedInteger('moq')->default(50);
            $table->unsignedInteger('carton_quantity')->default(100);
            $table->unsignedInteger('order_step')->default(25);

            $table->string('origin')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'is_published']);
        });

        /*
         | Laravel builds a pivot table name by sorting the two model names
         | alphabetically, so colourway comes before product. Naming it the
         | other way round leaves both belongsToMany relations looking for a
         | table that does not exist.
         */
        Schema::create('colourway_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('colourway_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['product_id', 'colourway_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colourway_product');
        Schema::dropIfExists('products');
    }
};
