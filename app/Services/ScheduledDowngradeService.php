<?php

namespace App\Services;

use App\Models\ClientSubscription;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies downgrades that were scheduled for the end of a paid term.
 *
 * scheduleDowngrade() stores renewal_plan_id on the subscription and takes no
 * payment, so the customer keeps the plan they paid for until it expires. Until
 * this service existed nothing ever read that key back: at expiry
 * getActiveSubscription() stopped matching (it filters expires_at > now) and
 * PlanEntitlementService fell through to client_free. A customer who chose
 * Carbon silently landed on Free.
 *
 * A downgrade to Free needs no work -- the fallback already IS Free -- so only
 * paid targets are processed here.
 */
class ScheduledDowngradeService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
    ) {
    }

    /**
     * @return array{applied: int, skipped: int, failed: int}
     */
    public function applyDue(bool $dryRun = false): array
    {
        $applied = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->dueSubscriptions() as $subscription) {
            $planId = $subscription->metadata['renewal_plan_id'] ?? null;

            // is_active is deliberately NOT filtered: a plan retired between
            // the customer choosing it and the term ending is still what they
            // agreed to, and existing subscribers already keep retired plans
            // (see LEGACY_PLAN_CODES). Withholding it would take away what was
            // agreed for a reason on our side, not theirs.
            $plan = $planId ? SubscriptionPlan::find($planId) : null;

            if (!$plan) {
                // The row is gone entirely -- nothing to apply. Clear the
                // pointer so this is not retried every night forever.
                if (!$dryRun) {
                    $this->subscriptions->clearScheduledDowngrade($subscription);
                }
                $skipped++;
                continue;
            }

            // Free targets need no activation: expiry already falls through to
            // client_free in PlanEntitlementService.
            if ((float) $plan->price_annual <= 0) {
                if (!$dryRun) {
                    $this->subscriptions->clearScheduledDowngrade($subscription);
                }
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $applied++;
                continue;
            }

            try {
                $this->apply($subscription, $plan);
                $applied++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Scheduled downgrade failed', [
                    'subscription_id' => $subscription->id,
                    'company_id' => $subscription->company_id,
                    'target_plan_id' => $plan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['applied' => $applied, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Expired subscriptions still carrying a scheduled downgrade.
     *
     * status is not filtered to 'active' only: the point is that the term has
     * ended, and a row may already have been marked otherwise.
     */
    protected function dueSubscriptions()
    {
        return ClientSubscription::query()
            ->where('expires_at', '<=', now())
            ->whereNotNull('metadata->renewal_plan_id')
            ->with('plan')
            ->get();
    }

    /**
     * Activate the target plan for a fresh term and raise an invoice for it.
     *
     * Service is granted BEFORE payment: nothing in this system charges a card
     * without the customer present, so billing after the fact is the only
     * option that does not interrupt a paying customer. The transaction is
     * created as 'pending' and the invoice issued against it, which is what
     * makes it appear on the billing page and in the receipt email.
     */
    protected function apply(ClientSubscription $subscription, SubscriptionPlan $plan): void
    {
        DB::transaction(function () use ($subscription, $plan) {
            $companyId = $subscription->company_id;
            // AED, not CurrencyService::displayCurrency(): that reads the
            // session, and this runs in cron with no user. price_annual is the
            // AED list price, so the invoice is denominated in the currency the
            // plan is actually priced in.
            $currency = 'AED';
            $amount = (float) $plan->price_annual;

            $transaction = PaymentTransaction::create([
                'company_id' => $companyId,
                'transaction_type' => 'subscription',
                'amount' => $amount,
                'currency' => $currency,
                // Not 'completed': no money has moved. The invoice records what
                // is owed for the term that was just granted.
                'status' => 'pending',
                'payment_method' => 'invoice',
                'description' => $plan->plan_name . ' — scheduled plan change',
                'metadata' => [
                    'transaction_type' => 'subscription',
                    'scheduled_downgrade' => true,
                    'previous_plan_id' => $subscription->subscription_plan_id,
                ],
            ]);

            // subscribeClient() clears renewal_plan_id itself (it treats any
            // plan change as ending the schedule), so this must NOT also call
            // clearScheduledDowngrade() -- re-fetching and re-saving metadata
            // after the fact risks writing back keys it just removed.
            $new = $this->subscriptions->subscribeClient($companyId, $plan->id, [
                'payment_method' => 'invoice',
                'auto_renew' => true,
            ]);

            $transaction->update(['subscription_id' => $new?->id]);
        });
    }
}
