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
        $lines = $request->normalizedLines();
        if ($lines !== []) {
            return CommercialPriceBook::suggestConsultantLinesQuote($lines);
        }

        return CommercialPriceBook::suggestConsultantQuote(
            (int) $request->entity_count,
            (bool) $request->wants_enterprise,
            $request->package_code,
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

        // Fallback for pre-Phase-8 DBs that only have starter/growth.
        if (!$plan) {
            $legacy = [
                'client_scope_basic' => 'client_starter',
                'client_scope_pro' => 'client_growth',
                'client_esg_starter' => 'client_growth',
                'client_esg_complete' => 'client_growth',
            ][$liveCode] ?? null;
            if ($legacy) {
                $plan = SubscriptionPlan::where('plan_code', $legacy)->where('plan_category', 'client')->first();
                $liveCode = $legacy;
            }
        }

        if (!$plan) {
            throw new RuntimeException("Live plan `{$liveCode}` not found. Run Phase 8 migrations or assign manually.");
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
     * Grant one consultant_subscriptions depth row per request line (Phase 3).
     * Does not overwrite a single agency managed_client_package_code as the sole depth.
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

        $lines = $request->normalizedLines();
        if ($lines === []) {
            throw new RuntimeException('Request has no package lines to activate.');
        }

        $contractYear = $contractYear ?? (int) now()->year;
        $this->consultantSubscriptions->ensureFreeTrialSubscription($org);

        return DB::transaction(function () use ($request, $org, $lines, $note, $adminId, $contractYear) {
            $created = [];

            foreach ($lines as $line) {
                $clientCode = $line['package_code'];
                $qty = max(1, (int) $line['entity_count']);
                $packCode = CommercialPriceBook::suggestedConsultantPlanCode($clientCode);

                $plan = SubscriptionPlan::where('plan_code', $packCode)
                    ->where('plan_category', 'consultant_agency')
                    ->first();

                if (!$plan) {
                    throw new RuntimeException(
                        "Consultant depth plan `{$packCode}` not found. Run Phase 1 migrations."
                    );
                }

                $subscription = $this->consultantSubscriptions->grantDepthSubscription(
                    $org,
                    $packCode,
                    $qty,
                    $contractYear,
                    [
                        'provision_note' => $note,
                        'consultant_entity_request_id' => $request->id,
                        'requested_clients' => $qty,
                        'client_package_code' => $clientCode,
                        'managed_client_package_code' => $clientCode,
                    ],
                    $adminId,
                );

                AdminPackageAssignment::create([
                    'admin_id' => $adminId,
                    'company_id' => $org->id,
                    'subscription_plan_id' => $plan->id,
                    'target_type' => 'consultant',
                    'contract_year' => $contractYear,
                    'note' => $note . " · {$packCode} ×{$qty}",
                    'status' => 'approved',
                    'consultant_subscription_id' => $subscription->id,
                    'metadata' => [
                        'provision_type' => 'request_activate_depth',
                        'consultant_entity_request_id' => $request->id,
                        'entity_count' => $qty,
                        'quote_amount_aed' => $request->quote_amount_aed,
                        'package_code' => $clientCode,
                        'client_package_code' => $clientCode,
                        'slot_limit' => $subscription->slot_limit,
                        'plan_code' => $packCode,
                        'request_lines' => $request->normalizedLines(),
                    ],
                ]);

                $created[] = [
                    'plan_code' => $packCode,
                    'slot_limit' => $subscription->slot_limit,
                    'subscription_id' => $subscription->id,
                ];
            }

            $request->update([
                'status' => 'activated',
                'activated_at' => now(),
                'paid_at' => $request->paid_at ?? now(),
                'entity_count' => $request->totalEntityCount(),
            ]);

            return $request->fresh();
        });
    }
}
