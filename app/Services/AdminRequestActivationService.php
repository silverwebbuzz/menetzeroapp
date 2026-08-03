<?php

namespace App\Services;

use App\Data\CommercialPriceBook;
use App\Models\AdminPackageAssignment;
use App\Models\Company;
use App\Models\CompanyPackageRequest;
use App\Models\ConsultantEntityRequest;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Phase 6 — quote helpers + activate from request inboxes.
 */
class AdminRequestActivationService
{
    public function __construct(
        protected SubscriptionService $clientSubscriptions,
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
    ) {
    }

    /**
     * @return array{amount_aed: float|null, custom: bool, label: string, live_plan_code: string|null, breakdown: string, band: string|null}
     */
    public function suggestCompanyQuote(CompanyPackageRequest $request): array
    {
        return CommercialPriceBook::suggestCompanyQuote($request->package_code);
    }

    /**
     * @return array{amount_aed: float|null, custom: bool, rate_aed: float|null, entity_count: int, breakdown: string, band: string, suggested_pack_code: string|null}
     */
    public function suggestConsultantQuote(ConsultantEntityRequest $request): array
    {
        return CommercialPriceBook::suggestConsultantQuote(
            (int) $request->entity_count,
            (bool) $request->wants_enterprise,
        );
    }

    public function saveCompanyQuote(
        CompanyPackageRequest $request,
        ?float $amountAed,
        ?string $breakdown,
        int $durationMonths,
        ?int $adminId,
    ): CompanyPackageRequest {
        $status = in_array($request->status, ['new', 'contacted', 'quoted'], true)
            ? 'quoted'
            : $request->status;

        $request->update([
            'quote_amount_aed' => $amountAed,
            'quote_breakdown' => $breakdown,
            'duration_months' => $durationMonths,
            'quoted_at' => now(),
            'status' => $status,
        ]);

        return $request->fresh();
    }

    public function saveConsultantQuote(
        ConsultantEntityRequest $request,
        ?float $amountAed,
        ?string $breakdown,
        ?int $adminId,
    ): ConsultantEntityRequest {
        $status = in_array($request->status, ['new', 'contacted', 'quoted'], true)
            ? 'quoted'
            : $request->status;

        $request->update([
            'quote_amount_aed' => $amountAed,
            'quote_breakdown' => $breakdown,
            'quoted_at' => now(),
            'status' => $status,
        ]);

        return $request->fresh();
    }

    public function markCompanyPaid(CompanyPackageRequest $request): CompanyPackageRequest
    {
        $request->update([
            'paid_at' => now(),
        ]);

        return $request->fresh();
    }

    public function markConsultantPaid(ConsultantEntityRequest $request): ConsultantEntityRequest
    {
        $request->update([
            'paid_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Grant live client plan and mark request activated.
     */
    public function activateCompanyRequest(
        CompanyPackageRequest $request,
        int $durationMonths,
        string $note,
        ?int $adminId,
    ): CompanyPackageRequest {
        if ($request->status === 'activated') {
            throw new RuntimeException('This package request is already activated.');
        }

        $company = Company::findOrFail($request->company_id);
        if (!$company->isClient()) {
            throw new RuntimeException('Company is not a client organisation.');
        }

        $liveCode = CommercialPriceBook::COMPANY_LIVE_PLAN_MAP[$request->package_code] ?? null;
        if (!$liveCode) {
            throw new RuntimeException('Unknown package code: ' . $request->package_code);
        }

        $plan = SubscriptionPlan::where('plan_code', $liveCode)
            ->where('plan_category', 'client')
            ->first();

        if (!$plan) {
            throw new RuntimeException("Live plan `{$liveCode}` not found. Seed plans or assign manually.");
        }

        $expiresAt = Carbon::now()->addMonths(max(1, $durationMonths));

        return DB::transaction(function () use ($request, $company, $plan, $expiresAt, $note, $adminId, $durationMonths) {
            $subscription = $this->clientSubscriptions->grantComplimentary(
                $company->id,
                $plan->id,
                $expiresAt,
                $note,
                $adminId,
            );

            AdminPackageAssignment::create([
                'admin_id' => $adminId,
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'target_type' => 'client',
                'duration_months' => $durationMonths,
                'note' => $note,
                'status' => 'approved',
                'client_subscription_id' => $subscription->id,
                'metadata' => [
                    'provision_type' => 'request_activate',
                    'company_package_request_id' => $request->id,
                    'request_package_code' => $request->package_code,
                    'quote_amount_aed' => $request->quote_amount_aed,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]);

            $request->update([
                'status' => 'activated',
                'activated_at' => now(),
                'duration_months' => $durationMonths,
                'paid_at' => $request->paid_at ?? now(),
            ]);

            return $request->fresh();
        });
    }

    /**
     * Grant/ensure agency slots for entity count and mark request activated.
     */
    public function activateConsultantRequest(
        ConsultantEntityRequest $request,
        string $note,
        ?int $adminId,
        ?int $contractYear = null,
    ): ConsultantEntityRequest {
        if ($request->status === 'activated') {
            throw new RuntimeException('This entity request is already activated.');
        }

        $org = Company::findOrFail($request->consultant_company_id);
        if (!$org->isConsultantOrg()) {
            throw new RuntimeException('Company is not a consultant organisation.');
        }

        $needed = max(1, (int) $request->entity_count);
        $contractYear = $contractYear ?? (int) now()->year;
        $packCode = CommercialPriceBook::nearestAgencyPackCode($needed);

        return DB::transaction(function () use ($request, $org, $needed, $packCode, $note, $adminId, $contractYear) {
            $active = $this->consultantSubscriptions->getActiveSubscription($org->id);
            $subscription = null;

            $isPaidActive = $active && !$active->isFreeTrial();

            if (!$isPaidActive) {
                $subscription = $this->consultantSubscriptions->grantPackSubscription(
                    $org,
                    $packCode,
                    $contractYear,
                    [
                        'provision_note' => $note,
                        'consultant_entity_request_id' => $request->id,
                    ],
                    $adminId,
                );

                $extras = CommercialPriceBook::extraSlotsNeeded($needed, $packCode);
                if ($extras > 0) {
                    $subscription = $this->consultantSubscriptions->addExtraSlots($subscription, $extras);
                }

                $plan = SubscriptionPlan::where('plan_code', $packCode)->first();
                AdminPackageAssignment::create([
                    'admin_id' => $adminId,
                    'company_id' => $org->id,
                    'subscription_plan_id' => $plan?->id,
                    'target_type' => 'consultant',
                    'contract_year' => $contractYear,
                    'note' => $note,
                    'status' => 'approved',
                    'consultant_subscription_id' => $subscription->id,
                    'metadata' => [
                        'provision_type' => 'request_activate',
                        'consultant_entity_request_id' => $request->id,
                        'entity_count' => $needed,
                        'quote_amount_aed' => $request->quote_amount_aed,
                        'wants_enterprise' => $request->wants_enterprise,
                        'slot_limit' => $subscription->slot_limit,
                    ],
                ]);
            } else {
                $subscription = $active;
                $shortfall = max(0, $needed - (int) $subscription->slot_limit);
                if ($shortfall > 0) {
                    $subscription = $this->consultantSubscriptions->addExtraSlots($subscription, $shortfall);
                }

                AdminPackageAssignment::create([
                    'admin_id' => $adminId,
                    'company_id' => $org->id,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'target_type' => 'consultant',
                    'contract_year' => $contractYear,
                    'note' => $note . ($shortfall > 0 ? " (+{$shortfall} slots)" : ' (capacity already sufficient)'),
                    'status' => 'approved',
                    'consultant_subscription_id' => $subscription->id,
                    'metadata' => [
                        'provision_type' => 'request_activate_slots',
                        'consultant_entity_request_id' => $request->id,
                        'entity_count' => $needed,
                        'slots_added' => $shortfall,
                        'quote_amount_aed' => $request->quote_amount_aed,
                        'slot_limit' => $subscription->slot_limit,
                    ],
                ]);
            }

            $request->update([
                'status' => 'activated',
                'activated_at' => now(),
                'paid_at' => $request->paid_at ?? now(),
            ]);

            return $request->fresh();
        });
    }
}
