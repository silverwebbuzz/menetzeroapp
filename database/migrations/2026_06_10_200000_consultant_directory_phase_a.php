<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('company_name');
            $table->string('trade_license_number', 80)->nullable();
            $table->text('bio')->nullable();
            $table->json('emirates')->nullable();
            $table->json('languages')->nullable();
            $table->json('specialties')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin')->nullable();
            $table->boolean('has_moccae_experience')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'suspended'])->default('draft');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('consultant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->string('file_path');
            $table->string('original_filename');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('consultant_intro_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained()->cascadeOnDelete();
            $table->string('pack_type', 40)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'closed'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        // C10 stub — escrow marketplace orders
        Schema::create('consultant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intro_request_id')->nullable()->constrained('consultant_intro_requests')->nullOnDelete();
            $table->string('pack_type', 40)->nullable();
            $table->decimal('amount_aed', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 4)->default(0.1500);
            $table->decimal('commission_aed', 10, 2)->default(0);
            $table->decimal('payout_aed', 10, 2)->default(0);
            $table->enum('escrow_status', ['pending_payment', 'held', 'released', 'refunded'])->default('pending_payment');
            $table->enum('order_status', ['draft', 'active', 'delivered', 'completed', 'disputed', 'cancelled'])->default('draft');
            $table->string('payment_reference')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_orders');
        Schema::dropIfExists('consultant_intro_requests');
        Schema::dropIfExists('consultant_documents');
        Schema::dropIfExists('consultants');
    }
};
