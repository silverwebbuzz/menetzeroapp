<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Services\ConsultantAgencyRenewalService;
use App\Services\ConsultantAgencySubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RenewalController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencyRenewalService $renewals,
        protected ConsultantAgencySubscriptionService $subscriptions,
    ) {
    }

    public function index()
    {
        $consultantOrg = $this->consultantCompany();
        $this->subscriptions->ensureFreeTrialSubscription($consultantOrg);

        $expiringSubscriptions = $this->renewals->subscriptionsNeedingRenewal($consultantOrg->id);
        $engagements = $this->renewals->boardEngagements($consultantOrg->id);

        if ($expiringSubscriptions->isEmpty() && $engagements->isEmpty()) {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'Renewal opens within 45 days of a capacity package expiry.');
        }

        $rows = [];
        foreach ($engagements as $engagement) {
            $targets = $this->renewals->continueTargetsFor($engagement);
            $defaultPry = (int) $engagement->primary_reporting_year + 1;
            $rows[] = [
                'engagement' => $engagement,
                'targets' => $targets,
                'default_pry' => $defaultPry,
            ];
        }

        $spareSeats = collect($this->subscriptions->availableCapacityBuckets($consultantOrg->id))
            ->sum('remaining');

        $nextYear = (int) ($expiringSubscriptions->first()?->contract_year
            ?? $engagements->first()?->subscription?->contract_year
            ?? now()->year) + 1;

        return view('consultant.agency.renewal.index', compact(
            'expiringSubscriptions',
            'rows',
            'spareSeats',
            'nextYear',
        ));
    }

    public function process(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

        $validated = $request->validate([
            'decisions' => 'required|array',
            'decisions.*.action' => 'required|in:leave,continue',
            'decisions.*.target_subscription_id' => 'nullable|integer',
            'decisions.*.primary_reporting_year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $decisions = [];
        foreach ($validated['decisions'] as $engagementId => $row) {
            $decisions[] = [
                'engagement_id' => (int) $engagementId,
                'action' => $row['action'],
                'target_subscription_id' => $row['target_subscription_id'] ?? null,
                'primary_reporting_year' => $row['primary_reporting_year'] ?? null,
            ];
        }

        try {
            $result = $this->renewals->applyBoardDecisions($consultantOrg, $decisions, Auth::id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $msg = sprintf(
            'Renewal applied: %d continued on new capacity, %d left as history (read-only).',
            $result['continued'],
            $result['left']
        );

        return redirect()
            ->route('consultant.workspace.switcher')
            ->with('success', $msg);
    }
}
