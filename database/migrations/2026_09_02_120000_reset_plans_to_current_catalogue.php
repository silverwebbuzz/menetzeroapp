<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move every company to Free and delete the superseded plan catalogue.
 *
 * Run only because NOBODY IS PAYING YET. Every prior migration deliberately
 * retired old plans with is_active = false rather than deleting them, because
 * "never delete a code somebody is still paying for" -- a missing row strips a
 * subscriber's entitlements at the next lookup. That reasoning still holds; it
 * simply does not apply while the paid customer count is zero.
 *
 * The migration ABORTS if it finds a completed payment, rather than trusting
 * that assumption blindly.
 *
 * Order matters. Four tables reference subscription_plans with three different
 * delete behaviours, so every reference is repointed BEFORE anything is
 * deleted:
 *
 *   client_subscriptions        -> repointed to client_free
 *   consultant_subscriptions    -> restrictOnDelete: MySQL REFUSES the delete
 *                                  while a row points at the plan
 *   admin_package_assignments   -> cascadeOnDelete: deleting a plan would
 *                                  silently destroy the assignment
 *   subscription_coupons        -> nullOnDelete: safe, but nulled explicitly
 *                                  so a coupon does not become plan-agnostic
 *                                  by accident
 */
return new class extends Migration
{
    /**
     * Plans that survive. The eight purchasable codes, plus two the code
     * requires by name: consultant_1 (DEMO_PACK_CODE, the free trial client)
     * and consultant_entity (ENTITY_PLAN_CODE). Both are absent from
     * CURRENT_PLAN_CODES but referenced in live paths -- deleting them breaks
     * the trial and entity flows.
     */
    private const KEEP = [
        'client_free',
        'client_carbon',
        'client_esg',
        'client_enterprise',
        'consultant_free',
        'consultant_carbon',
        'consultant_esg',
        'consultant_enterprise',
        'consultant_1',
        'consultant_entity',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        // Guard: this is only safe while nothing has been paid for. A single
        // completed payment means somebody owns a plan, and moving them to
        // Free would take away what they bought.
        if (Schema::hasTable('client_payment_transactions')) {
            $paid = DB::table('client_payment_transactions')
                ->where('status', 'completed')
                ->count();

            if ($paid > 0) {
                throw new RuntimeException(
                    "Aborting: {$paid} completed payment(s) exist. This migration moves every "
                    . 'company to Free and deletes plans, which would strip a paying customer. '
                    . 'Reconcile those payments first.'
                );
            }
        }

        $freeClientId = SubscriptionPlan::where('plan_code', 'client_free')->value('id');
        $freeConsultantId = SubscriptionPlan::where('plan_code', 'consultant_free')->value('id');

        if (!$freeClientId || !$freeConsultantId) {
            throw new RuntimeException(
                'client_free and consultant_free must exist before running this. '
                . 'Run: php artisan db:seed --class=SubscriptionPlanSeeder'
            );
        }

        $doomed = SubscriptionPlan::whereNotIn('plan_code', self::KEEP)->pluck('id');

        DB::transaction(function () use ($freeClientId, $freeConsultantId, $doomed) {

            // 1. Every company onto Free, whatever it was on. Not limited to
            //    doomed plans: the instruction is that all companies are Free.
            //
            //    status is deliberately NOT touched. client_subscriptions
            //    carries a generated column `active_company_key` = company_id
            //    WHEN status='active', under a UNIQUE index -- one active
            //    subscription per company. Forcing every row to 'active' would
            //    violate it for any company holding a cancelled or expired row
            //    alongside a live one, and abort the whole migration.
            DB::table('client_subscriptions')->update([
                'subscription_plan_id' => $freeClientId,
                'updated_at' => now(),
            ]);

            // 2. Consultants keep the pack they are on if it survives; only
            //    those on a deleted plan move to consultant_free. Their packs
            //    were just rebuilt and are the new catalogue.
            if ($doomed->isNotEmpty()) {
                DB::table('consultant_subscriptions')
                    ->whereIn('subscription_plan_id', $doomed)
                    ->update([
                        'subscription_plan_id' => $freeConsultantId,
                        'updated_at' => now(),
                    ]);

                // 3. cascadeOnDelete would take these with the plan. Repoint so
                //    the admin record survives and stays readable.
                if (Schema::hasTable('admin_package_assignments')) {
                    DB::table('admin_package_assignments')
                        ->whereIn('subscription_plan_id', $doomed)
                        ->update([
                            'subscription_plan_id' => $freeClientId,
                            'updated_at' => now(),
                        ]);
                }

                // 4. nullOnDelete would do this anyway; explicit is clearer.
                if (Schema::hasTable('subscription_coupons')) {
                    DB::table('subscription_coupons')
                        ->whereIn('subscription_plan_id', $doomed)
                        ->update([
                            'subscription_plan_id' => null,
                            'updated_at' => now(),
                        ]);
                }

                // 5. Now nothing references them.
                SubscriptionPlan::whereIn('id', $doomed)->delete();
            }
        });
    }

    /**
     * Not reversible. The deleted rows and the subscriptions' previous plan
     * assignments are gone; re-seeding would create new ids that nothing
     * points at. Restore from a backup instead.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
