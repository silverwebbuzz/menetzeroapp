<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;

/**
 * Align subscription_plans.description with shipped UAE ESG / Enterprise features.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->updateDescription('client_growth', 'Integrated UAE ESG Report, ESG Scorecard, IFRS, GRI & SASB exports.');
        $this->updateDescription('client_enterprise', '80+ GRI/KPI packs, HRIS import, assurance PDF, white-label UAE ESG PDF.');
    }

    public function down(): void
    {
        $this->updateDescription('client_growth', 'For expanding businesses needing more locations and users.');
        $this->updateDescription('client_enterprise', 'For large / multi-site organisations. Custom pricing (AED 20,000+).');
    }

    private function updateDescription(string $code, string $description): void
    {
        SubscriptionPlan::where('plan_code', $code)->update(['description' => $description]);
    }
};
