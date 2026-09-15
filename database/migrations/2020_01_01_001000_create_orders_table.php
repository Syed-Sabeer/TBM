<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();          // TBM-2026-3461
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('placed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 32)->default(OrderStatus::PendingConfirmation->value);
            $table->string('customer_po', 64)->nullable();
            $table->string('job_reference')->nullable();
            $table->string('shipping_service')->nullable();
            $table->string('payment_terms', 32)->nullable();
            $table->date('in_hands_on')->nullable();

            // Money is captured at the moment the order is placed. Changing a
            // rate card later must never rewrite history.
            $table->decimal('merchandise_total', 12, 2)->default(0);
            $table->decimal('decoration_total', 12, 2)->default(0);
            $table->decimal('freight_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->unsignedInteger('total_pieces')->default(0);

            // Snapshot of the shipping address, so an edited address never
            // rewrites a historical packing slip.
            $table->json('ship_to')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('placed_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('colourway_id')->nullable()->constrained()->nullOnDelete();

            // Denormalised so a document reprints exactly as it was issued.
            $table->string('sku', 32);
            $table->string('parent_sku', 32);
            $table->string('name');
            $table->string('colour_name');
            $table->string('size');
            $table->string('decoration')->default('Blank (undecorated)');

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 4);
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('sku');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            // An internal note is never rendered in the customer portal.
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
