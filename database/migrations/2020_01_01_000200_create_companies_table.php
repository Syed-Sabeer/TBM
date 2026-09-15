<?php

use App\Enums\AccountStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 32)->unique();     // C10428
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->string('business_type')->nullable();

            $table->foreignId('price_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 32)->default(AccountStatus::PendingApproval->value);
            $table->string('payment_terms', 32)->default('Prepay');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_used', 12, 2)->default(0);

            $table->string('ein', 32)->nullable();
            $table->string('resale_certificate', 64)->nullable();
            $table->string('certificate_status', 32)->default('awaiting');
            $table->date('certificate_expires_at')->nullable();

            $table->string('billing_street')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state', 32)->nullable();
            $table->string('billing_postcode', 16)->nullable();
            $table->string('billing_country', 64)->default('United States');

            $table->foreignId('default_warehouse_id')->nullable();
            $table->date('customer_since')->nullable();   // rendered as "January 2021"
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
