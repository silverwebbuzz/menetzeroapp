<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService)
    {
    }

    public function grant(Request $request, int $companyId)
    {
        $company = Company::where('company_type', 'client')->findOrFail($companyId);

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_months' => 'required|integer|min:1|max:60',
            'note' => 'required|string|max:500',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($plan->plan_category !== 'client' || (float) $plan->price_annual <= 0) {
            return back()->with('error', 'Choose a paid client plan (Starter, Growth, etc.).');
        }

        $expiresAt = Carbon::now()->addMonths((int) $request->duration_months);

        $this->subscriptionService->grantComplimentary(
            $company->id,
            $plan->id,
            $expiresAt,
            $request->note,
            Auth::id()
        );

        return redirect()->route('admin.companies.show', $company->id)
            ->with('success', "Granted complimentary {$plan->plan_name} until {$expiresAt->format('F d, Y')}.");
    }
}
