<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPackageAssignment;
use App\Models\Company;
use App\Models\Consultant;
use App\Models\SubscriptionPlan;
use App\Services\ConsultantAccountService;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Unified admin capability to assign any package to any client company or any
 * consultant agency organisation at no charge. Each assignment is recorded in
 * the admin_package_assignments audit table (who approved, what, for whom).
 */
class AdminPackageAssignmentController extends Controller
{
    public function __construct(
        protected SubscriptionService $clientSubscriptions,
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
    ) {
    }

    /**
     * Full audit log of admin-assigned packages.
     */
    public function index()
    {
        $assignments = AdminPackageAssignment::with(['company', 'consultant', 'plan', 'admin'])
            ->latest()
            ->paginate(30);

        return view('admin.package-assignments.index', compact('assignments'));
    }

    /**
     * Assign a package to a company — auto-routes to the correct flow based on
     * whether the company is a consultant organisation or a client.
     */
    public function assignToCompany(Request $request, int $companyId)
    {
        $company = Company::findOrFail($companyId);

        if ($company->isConsultantOrg()) {
            return $this->assignConsultantPack($request, $company, null);
        }

        return $this->assignClientPlan($request, $company);
    }

    /**
     * Assign a consultant agency pack directly from a consultant record.
     * Ensures the consultant is linked to an agency organisation first.
     */
    public function assignToConsultant(Request $request, Consultant $consultant)
    {
        ['company' => $consultantOrg] = app(ConsultantAccountService::class)->ensureLinked($consultant);

        return $this->assignConsultantPack($request, $consultantOrg, $consultant);
    }

    /**
     * Grant a complimentary paid client plan and record the approval.
     */
    protected function assignClientPlan(Request $request, Company $company)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_months' => 'required|integer|min:1|max:60',
            'note' => 'required|string|max:500',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if ($plan->plan_category !== 'client' || (float) $plan->price_annual <= 0) {
            return back()->with('error', 'Choose a paid client plan (Starter, Growth, etc.).');
        }

        $expiresAt = Carbon::now()->addMonths((int) $validated['duration_months']);
        $adminId = Auth::guard('admin')->id();

        $subscription = $this->clientSubscriptions->grantComplimentary(
            $company->id,
            $plan->id,
            $expiresAt,
            $validated['note'],
            $adminId,
        );

        AdminPackageAssignment::create([
            'admin_id' => $adminId,
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'target_type' => 'client',
            'duration_months' => (int) $validated['duration_months'],
            'note' => $validated['note'],
            'status' => 'approved',
            'client_subscription_id' => $subscription->id,
            'metadata' => [
                'provision_type' => 'admin_comp',
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);

        return back()->with(
            'success',
            "Assigned {$plan->plan_name} to {$company->name} until {$expiresAt->format('F d, Y')}."
        );
    }

    /**
     * Grant a consultant agency pack and record the approval.
     */
    protected function assignConsultantPack(Request $request, Company $consultantOrg, ?Consultant $consultant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'contract_year' => 'nullable|integer|min:2024|max:2100',
            'note' => 'required|string|max:500',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if (!$plan->isConsultantAgencyPack()) {
            return back()->with('error', 'Choose a consultant agency pack (Consultant 5 / 10 / 25 / 50).');
        }

        $contractYear = (int) ($validated['contract_year'] ?? now()->year);
        $adminId = Auth::guard('admin')->id();

        $subscription = $this->consultantSubscriptions->grantPackSubscription(
            $consultantOrg,
            $plan->plan_code,
            $contractYear,
            ['provision_note' => $validated['note']],
            $adminId,
        );

        AdminPackageAssignment::create([
            'admin_id' => $adminId,
            'company_id' => $consultantOrg->id,
            'consultant_id' => $consultant?->id,
            'subscription_plan_id' => $plan->id,
            'target_type' => 'consultant',
            'contract_year' => $contractYear,
            'note' => $validated['note'],
            'status' => 'approved',
            'consultant_subscription_id' => $subscription->id,
            'metadata' => [
                'provision_type' => 'admin_grant',
                'slot_limit' => $subscription->slot_limit,
            ],
        ]);

        return back()->with(
            'success',
            "Assigned {$plan->plan_name} to {$consultantOrg->name} for contract year {$contractYear}."
        );
    }
}
