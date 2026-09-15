<?php

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_imports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();          // IMP-20260913-4821
            $table->string('type', 32)->default(ImportType::Stock->value);
            $table->string('status', 32)->default(ImportStatus::Draft->value);
            $table->string('filename');
            $table->string('path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete(); // rate-card imports

            // Source column -> domain field, so a scheduled run repeats it.
            $table->json('column_map')->nullable();

            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_added')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('warnings')->default(0);

            $table->boolean('was_scheduled')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number');

            $table->string('parent_sku', 32)->nullable();
            $table->string('description')->nullable();
            $table->string('shade')->nullable();
            $table->string('warehouse_code', 8)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->date('ready_on')->nullable();

            // Resolved during preview.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('colourway_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity_before')->nullable();
            $table->string('status', 32)->default('unmatched');
            $table->string('message')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['stock_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_import_rows');
        Schema::dropIfExists('stock_imports');
    }
};
