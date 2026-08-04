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

        $rowSections = collect($buckets)->map(function (array $bucket) use ($bySubscription) {
            $id = (int) $bucket['subscription_id'];
            $list = $bySubscription->get($id, collect())->values();
            $bySubscription->forget($id);

            return array_merge($bucket, [
                'engagements' => $list,
            ]);
        })->values();

        // Display: merge same plan_code into one section; DB rows stay separate.
        $sections = $rowSections
            ->groupBy(fn (array $row) => $row['plan_code'] ?: ('row-'.$row['subscription_id']))
            ->map(function ($group) {
                $rows = $group->sortBy(fn (array $r) => $r['expires_at'] ?? '9999-99-99')->values();
                $first = $rows->first();

                $emptySeatTargets = [];
                foreach ($rows as $row) {
                    $left = max(0, (int) $row['remaining']);
                    for ($i = 0; $i < $left; $i++) {
                        $emptySeatTargets[] = (int) $row['subscription_id'];
                    }
                }

                $expires = $rows->pluck('expires_at')->filter()->unique()->values();
                $expiresLabel = null;
                if ($expires->count() === 1) {
                    $expiresLabel = 'expires '.$expires->first();
                } elseif ($expires->count() > 1) {
                    $expiresLabel = 'expires '.$expires->min().' – '.$expires->max();
                }

                return [
                    'plan_code' => $first['plan_code'],
                    'plan_name' => $first['plan_name'],
                    'client_package_code' => $first['client_package_code'],
                    'is_trial' => (bool) $first['is_trial'],
                    'is_demo' => (bool) $first['is_demo'],
                    'is_depth' => (bool) $first['is_depth'],
                    'used' => (int) $rows->sum('used'),
                    'slot_limit' => (int) $rows->sum('slot_limit'),
                    'remaining' => (int) $rows->sum('remaining'),
                    'expires_label' => $expiresLabel,
                    'purchase_rows' => $rows->map(fn (array $r) => [
                        'subscription_id' => (int) $r['subscription_id'],
                        'slot_limit' => (int) $r['slot_limit'],
                        'used' => (int) $r['used'],
                        'remaining' => (int) $r['remaining'],
                        'expires_at' => $r['expires_at'],
                    ])->all(),
                    'engagements' => $rows->flatMap(fn (array $r) => $r['engagements'])->values(),
                    'empty_seat_targets' => $emptySeatTargets,
                ];
            })
            ->values();

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
