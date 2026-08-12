<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Raise the Scope 3 per-category entry cap from 1 to 12 on paid company plans.
 *
 * Why
 * ---
 * `scope3_records_per_form` was 1 on every plan except Enterprise, so a company could
 * hold only ONE Scope 3 entry per category per measurement. Live data confirmed the
 * effect: across 893 measurement_data rows, the maximum entries in any
 * (measurement, category) pair was exactly 1.
 *
 * That makes bulk import pointless — an upload could never insert more than 15 rows
 * (one per GHG Protocol category). 12 allows one entry per month per category, which
 * covers monthly utility/travel/waste billing cycles.
 *
 * Scope 1 & 2 already offers bulk import with no per-form cap on paid plans, so this
 * also removes an inconsistency that was very likely unintentional.
 *
 * Free is deliberately left at 1 — it is the upgrade lever, and `bulk_import` is
 * already false there.
 *
 * Two places, not one
 * -------------------
 * PlanEntitlementService::getScope3RecordsPerFormLimit() reads:
 *   - `subscription_plans.limits` JSON  → direct subscribers   (this migration)
 *   - PlanEntitlementDefaults           → managed/consultant clients (code change)
 * Both must move together or the change silently has no effect for one audience.
 */
return new class extends Migration
{
    /** Paid company plans that should allow 12 entries per Scope 3 category. */
    private const PLAN_CODES = [
        'client_scope_basic',
        'client_scope_pro',
        'client_esg_starter',
        'client_esg_complete',
        // Legacy but still carrying live subscribers / reactivatable.
        'client_starter',
        'client_growth',
    ];

    private const NEW_CAP = 12;

    public function up(): void
    {
        $this->applyCap(self::NEW_CAP, 1);
    }

    public function down(): void
    {
        $this->applyCap(1, self::NEW_CAP);
    }

    /**
     * Rewrite the cap inside the plan's `limits` JSON, leaving every other key intact.
     *
     * Only rows whose current value matches $expectedCurrent are touched, so a cap that
     * an admin has since tuned by hand is never clobbered.
     */
    private function applyCap(int $newCap, int $expectedCurrent): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = DB::table('subscription_plans')
            ->whereIn('plan_code', self::PLAN_CODES)
            ->get(['id', 'plan_code', 'limits']);

        foreach ($plans as $plan) {
            $limits = json_decode((string) $plan->limits, true);

            if (!is_array($limits)) {
                continue;
            }

            $current = $limits['scope3_records_per_form'] ?? null;

            // Skip unlimited (-1) and anything already customised away from the
            // value this migration expects to find.
            if ((int) $current !== $expectedCurrent) {
                continue;
            }

            $limits['scope3_records_per_form'] = $newCap;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'limits' => json_encode($limits),
                    'updated_at' => now(),
                ]);
        }
    }
};
