<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultants need their own reset-token store: a consultant almost always has a
 * User row with the same email (created by ConsultantAccountService), and
 * password_reset_tokens is keyed by email — sharing it would let a consultant
 * reset overwrite the company user's pending token, and vice versa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_password_reset_tokens');
    }
};
