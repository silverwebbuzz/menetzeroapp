<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tax invoices issued against completed payments.
 *
 * Separate from client_payment_transactions on purpose. A transaction is a
 * payment attempt -- it can fail, retry, or be superseded. An invoice is a
 * legal document: once issued its number is spent, and the buyer's name and
 * address must stay as they were on the issue date even if the company later
 * renames itself. Storing the buyer as flat text rather than a join is
 * deliberate for that reason.
 *
 * Amounts are stored in the QUOTED currency (AED). When Razorpay's INR
 * fallback fires, the settled figure goes in charged_amount/charged_currency
 * so the invoice can show both without restating the price that was agreed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Unique at the database level, not just in application code: the
            // number is the legal identity of the document, and a duplicate
            // under a race is worse than a failed insert.
            $table->string('invoice_number', 40)->unique();

            $table->foreignId('transaction_id')->nullable()
                ->constrained('client_payment_transactions')->nullOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Buyer snapshot at issue time.
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->text('buyer_address')->nullable();
            $table->string('buyer_trn', 40)->nullable();

            // Seller snapshot: our own details can change between invoices.
            $table->string('seller_name')->nullable();
            $table->text('seller_address')->nullable();
            $table->string('seller_trn', 40)->nullable();

            $table->string('description')->nullable();
            $table->string('currency', 3)->default('AED');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Only populated when the settlement currency differs from the
            // quoted one (the AED -> INR Razorpay fallback).
            $table->decimal('charged_amount', 12, 2)->nullable();
            $table->string('charged_currency', 3)->nullable();

            $table->string('payment_method')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
