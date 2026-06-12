<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('mailer', 20)->default('noreply');
            $table->string('reply_to', 20)->nullable();
            $table->string('subject');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        (new \Database\Seeders\EmailTemplateSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
