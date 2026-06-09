<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\PlanEntitlementAdminService;
use Illuminate\Http\Request;

class PlanEntitlementController extends Controller
{
    public function __construct(
        protected PlanEntitlementAdminService $adminService,
    ) {
    }

    public function edit(int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $form = $this->adminService->formValuesFromPlan($plan);

        return view('admin.subscription-plans.entitlements', [
            'plan' => $plan,
            'form' => $form,
            'scope3Modes' => PlanEntitlementAdminService::scope3ModeOptions(),
            'helpLevels' => PlanEntitlementAdminService::helpLevelOptions(),
            'consultantLevels' => PlanEntitlementAdminService::consultantDirectoryOptions(),
            'exportRegenModes' => PlanEntitlementAdminService::exportRegenOptions(),
            'exportOptions' => PlanEntitlementAdminService::exportOptions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $request->validate([
            'locations' => 'required|integer|min:-1',
            'users' => 'required|integer|min:-1',
            'scope3_mode' => 'required|in:locked,preview_per_category,full',
            'help_level' => 'required|in:basic,full,full_disclosures',
            'consultant_directory' => 'required|in:teaser,partial,full,priority',
            'export_regen' => 'required|in:none,subscription_year_unlimited',
            'exports' => 'nullable|array',
            'exports.*' => 'string',
        ]);

        $this->adminService->applyToPlan($plan, $request->all());

        return redirect()
            ->route('admin.subscription-plans.entitlements', $plan->id)
            ->with('success', 'Entitlements updated for ' . $plan->plan_name . '.');
    }

    public function resetDefaults(int $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $this->adminService->resetToDefaults($plan);

        return redirect()
            ->route('admin.subscription-plans.entitlements', $plan->id)
            ->with('success', 'Reset to Commercial Plan v1 defaults.');
    }
}
