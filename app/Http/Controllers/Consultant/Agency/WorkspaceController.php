<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Models\ConsultantClientEngagement;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\ConsultantAgencyWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WorkspaceController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencyWorkspaceService $workspace,
        protected ConsultantAgencySubscriptionService $subscriptions,
    ) {
    }

    public function switcher()
    {
        $user = Auth::user();
        $consultantOrg = $this->consultantCompany();
        $this->subscriptions->ensureFreeTrialSubscription($consultantOrg);

        $engagements = $this->workspace->switchableEngagements($user);
        $acting = $this->workspace->resolveActingCompany($user);
        $buckets = $this->subscriptions->capacityBuckets($consultantOrg->id);

        $bySubscription = $engagements->groupBy(
            fn (ConsultantClientEngagement $e) => (int) $e->consultant_subscription_id
        );

        $sections = collect($buckets)->map(function (array $bucket) use ($bySubscription) {
            $id = (int) $bucket['subscription_id'];
            $list = $bySubscription->get($id, collect())->values();
            $bySubscription->forget($id);

            return array_merge($bucket, [
                'engagements' => $list,
            ]);
        })->values();

        // Active clients whose capacity row is no longer active (expired/cancelled).
        $orphanEngagements = $bySubscription->flatten(1)->values();

        return view('consultant.agency.workspace.switcher', compact(
            'sections',
            'orphanEngagements',
            'acting',
        ));
    }

    public function enter(Request $request, int $engagement)
    {
        $user = Auth::user();
        $record = ConsultantClientEngagement::findOrFail($engagement);

        try {
            $managed = $this->workspace->enterWorkspaceFromEngagement($user, $record);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('success', "Now working in {$managed->name} (PRY {$record->primary_reporting_year}).");
    }

    public function enterReadOnly(int $engagement)
    {
        $user = Auth::user();
        $record = ConsultantClientEngagement::findOrFail($engagement);

        try {
            $managed = $this->workspace->enterReadOnlyWorkspace($user, $record);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('info', "Read-only view of {$managed->name} (archived engagement).");
    }

    public function exit()
    {
        $this->workspace->exitWorkspace();

        return redirect()
            ->route('consultant.dashboard')
            ->with('success', 'Returned to agency hub.');
    }
}
