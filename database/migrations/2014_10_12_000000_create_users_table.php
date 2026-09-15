<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            /*
             | company_id is declared without its constraint here, because
             | users and companies point at each other: a customer login
             | belongs to a company, and a company names one of the staff
             | logins as its account manager. One of the two has to be created
             | first, so the foreign key is closed once both tables exist —
             | see 2020_01_01_001400_add_company_foreign_keys.
             |
             | NULL means staff. A customer always belongs to exactly one
             | company.
             */
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
