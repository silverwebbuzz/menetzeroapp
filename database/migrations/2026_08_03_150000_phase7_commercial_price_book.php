<?php

use App\Models\CommercialPriceBookEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_price_book_entries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('category', 32); // company_package | consultant_rate | extra
            $table->string('label');
            $table->decimal('amount_aed', 12, 2)->nullable();
            $table->boolean('is_custom')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('category');
        });

        $rows = [
            ['code' => 'client_scope_basic', 'category' => 'company_package', 'label' => 'Scope Basic', 'amount_aed' => 2500, 'is_custom' => false, 'notes' => 'xlsx §5 · up to 3 sites · clean GHG/MOCCAE/Excel/IEQT', 'sort_order' => 10],
            ['code' => 'client_scope_pro', 'category' => 'company_package', 'label' => 'Scope Pro', 'amount_aed' => 4999, 'is_custom' => false, 'notes' => 'xlsx §5 · up to 10 sites · ESG disclosure suite', 'sort_order' => 20],
            ['code' => 'client_esg_starter', 'category' => 'company_package', 'label' => 'ESG Starter', 'amount_aed' => 18000, 'is_custom' => false, 'notes' => 'xlsx §5 · up to 5 sites · full ESG', 'sort_order' => 30],
            ['code' => 'client_esg_complete', 'category' => 'company_package', 'label' => 'ESG Complete', 'amount_aed' => 36000, 'is_custom' => false, 'notes' => 'xlsx §5 · up to 10 sites · multi-entity options', 'sort_order' => 40],
            ['code' => 'client_enterprise', 'category' => 'company_package', 'label' => 'Enterprise', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Custom / white-label — quote manually', 'sort_order' => 50],
            ['code' => 'consultant_rate_le_10', 'category' => 'consultant_rate', 'label' => 'Consultant Plan ≤10 entities', 'amount_aed' => 1399, 'is_custom' => false, 'notes' => '§6.2 intro · AED per entity / year excl. VAT', 'sort_order' => 10, 'meta' => ['max_entities' => 10]],
            ['code' => 'consultant_rate_gt_10', 'category' => 'consultant_rate', 'label' => 'Consultant Plan >10 entities', 'amount_aed' => 1199, 'is_custom' => false, 'notes' => '§6.2 intro · AED per entity / year excl. VAT', 'sort_order' => 20, 'meta' => ['min_entities' => 11]],
            ['code' => 'extra_company_sites', 'category' => 'extra', 'label' => 'Company — extra sites', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Quote by complexity', 'sort_order' => 10],
            ['code' => 'extra_company_seats', 'category' => 'extra', 'label' => 'Company — extra seats', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Quote by seats needed', 'sort_order' => 20],
            ['code' => 'extra_company_scope3', 'category' => 'extra', 'label' => 'Company — Scope 3 intensity', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Beyond package default', 'sort_order' => 30],
            ['code' => 'extra_company_white_label', 'category' => 'extra', 'label' => 'Company — white-label', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Covers / branding', 'sort_order' => 40],
            ['code' => 'extra_consultant_sites_over_5', 'category' => 'extra', 'label' => 'Consultant — sites beyond 5 / entity', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Quote by complexity', 'sort_order' => 50],
            ['code' => 'extra_consultant_agency_seats', 'category' => 'extra', 'label' => 'Consultant — agency team seats', 'amount_aed' => null, 'is_custom' => true, 'notes' => 'Optional fee', 'sort_order' => 60],
            ['code' => 'extra_consultant_enterprise', 'category' => 'extra', 'label' => 'Consultant — Enterprise / white-label', 'amount_aed' => null, 'is_custom' => true, 'notes' => '§6.4 custom', 'sort_order' => 70],
        ];

        foreach ($rows as $row) {
            CommercialPriceBookEntry::query()->create($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_price_book_entries');
    }
};
