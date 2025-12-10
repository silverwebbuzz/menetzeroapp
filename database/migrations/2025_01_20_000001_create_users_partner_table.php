<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_partner', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique(); // Email unique within partner table
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('company_id')->nullable();
            $table->enum('role', ['admin', 'company_admin', 'company_user'])->default('company_user');
            $table->boolean('is_active')->default(true);
            
            // Profile fields (same as users table)
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            
            // OAuth fields
            $table->string('google_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('provider')->nullable();
            
            // Additional fields (same as users table)
            // Note: user_type not needed - table name determines type
            $table->foreignId('custom_role_id')->nullable();
            $table->string('external_company_name')->nullable();
            $table->text('notes')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('company_id');
            $table->index('google_id');
            $table->index('custom_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_partner');
    }
};

