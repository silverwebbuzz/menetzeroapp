<?php

namespace App\Services;

use App\Models\Consultant;
use App\Models\ConsultantClientEngagement;
use App\Models\Location;
use App\Models\Measurement;
use Illuminate\Support\Collection;

class ConsultantDashboardService
{
    /**
     * @param  list<string>  $missingDocs
     * @return array{
     *   percent: int,
     *   steps: list<array{key: string, label: string, done: bool, url: string|null}>,
     *   ready_to_submit: bool
     * }
     */
    public function directoryProgress(Consultant $consultant, array $missingDocs): array
    {
        $steps = [
            [
                'key' => 'profile',
                'label' => 'Complete profile',
                'done' => filled($consultant->bio)
                    && is_array($consultant->emirates) && count($consultant->emirates) > 0
                    && is_array($consultant->specialties) && count($consultant->specialties) > 0,
                'url' => route('consultant.profile.edit'),
            ],
            [
                'key' => 'documents',
                'label' => 'Upload required documents',
                'done' => empty($missingDocs),
                'url' => route('consultant.documents.index'),
            ],
            [
                'key' => 'submit',
                'label' => 'Submit for review',
                'done' => in_array($consultant->status, ['pending_review', 'approved'], true),
                'url' => null,
            ],
        ];

        $doneCount = collect($steps)->where('done', true)->count();

        return [
            'percent' => (int) round(($doneCount / max(1, count($steps))) * 100),
            'steps' => $steps,
            'ready_to_submit' => $consultant->canSubmitForReview(),
        ];
    }

    /**
     * @param  Collection<int, ConsultantClientEngagement>  $engagements
     * @return array{
     *   total_emissions_kg: float,
     *   scope1_kg: float,
     *   scope2_kg: float,
     *   scope3_kg: float,
     *   locations_count: int,
     *   clients_with_data: int,
     *   sectors: list<string>,
     *   clients: list<array{
     *     id: int,
     *     name: string,
     *     sector: string|null,
     *     pry: int,
     *     emissions_kg: float,
     *     locations: int,
     *     is_trial: bool
     *   }>,
     *   scope_breakdown: array<string, float>
     * }
     */
    public function portfolioStats(Collection $engagements): array
    {
        $clients = [];
        $sectors = [];
        $totalKg = 0.0;
        $scope1 = 0.0;
        $scope2 = 0.0;
        $scope3 = 0.0;
        $locationsCount = 0;
        $clientsWithData = 0;

        foreach ($engagements as $engagement) {
            $company = $engagement->managedCompany;

            if (!$company) {
                continue;
            }

            $companyId = $company->id;
            $pry = (int) $engagement->primary_reporting_year;

            $measurements = Measurement::query()
                ->whereHas('location', fn ($q) => $q->where('company_id', $companyId))
                ->where('fiscal_year', $pry)
                ->get();

            $clientKg = (float) $measurements->sum('total_co2e');
            $clientScope1 = (float) $measurements->sum('scope_1_co2e');
            $clientScope2 = (float) $measurements->sum('scope_2_co2e');
            $clientScope3 = (float) $measurements->sum('scope_3_co2e');
            $clientLocations = Location::where('company_id', $companyId)->count();

            if ($clientKg > 0) {
                $clientsWithData++;
            }

            if ($company->sector) {
                $sectors[] = $company->sector;
            }

            $totalKg += $clientKg;
            $scope1 += $clientScope1;
            $scope2 += $clientScope2;
            $scope3 += $clientScope3;
            $locationsCount += $clientLocations;

            $clients[] = [
                'id' => $engagement->id,
                'name' => $engagement->display_name ?: $company->name,
                'sector' => $company->sector,
                'pry' => $pry,
                'emissions_kg' => $clientKg,
                'locations' => $clientLocations,
                'is_trial' => $engagement->subscription?->isFreeTrial() ?? false,
            ];
        }

        usort($clients, fn (array $a, array $b) => $b['emissions_kg'] <=> $a['emissions_kg']);

        return [
            'total_emissions_kg' => $totalKg,
            'scope1_kg' => $scope1,
            'scope2_kg' => $scope2,
            'scope3_kg' => $scope3,
            'locations_count' => $locationsCount,
            'clients_with_data' => $clientsWithData,
            'sectors' => array_values(array_unique($sectors)),
            'clients' => $clients,
            'scope_breakdown' => [
                'Scope 1' => $scope1,
                'Scope 2' => $scope2,
                'Scope 3' => $scope3,
            ],
        ];
    }

    /**
     * @return list<array{label: string, route: string, icon: string, primary: bool}>
     */
    public function quickActions(bool $hasSlotRemaining, bool $isTrial, bool $needsRenewal): array
    {
        $actions = [];

        if ($hasSlotRemaining) {
            $actions[] = [
                'label' => $isTrial ? 'Add first client' : 'Add client',
                'route' => route('consultant.clients.create'),
                'icon' => 'plus',
                'primary' => true,
            ];
        }

        $actions[] = [
            'label' => 'Open workspace',
            'route' => route('consultant.workspace.switcher'),
            'icon' => 'switch',
            'primary' => false,
        ];

        $actions[] = [
            'label' => $isTrial ? 'Upgrade pack' : 'Agency packs',
            'route' => route('consultant.packs.index'),
            'icon' => 'card',
            'primary' => $isTrial,
        ];

        if ($needsRenewal) {
            $actions[] = [
                'label' => 'Renew contract',
                'route' => route('consultant.renewal.index'),
                'icon' => 'refresh',
                'primary' => true,
            ];
        }

        $actions[] = [
            'label' => 'View leads',
            'route' => route('consultant.intro-requests.index'),
            'icon' => 'inbox',
            'primary' => false,
        ];

        return $actions;
    }

    /**
     * @param  Collection<int, ConsultantClientEngagement>  $engagements
     * @return array{
     *   pending_reviews: int,
     *   monthly_revenue: float,
     *   pipeline: array{new_leads: int, qualified: int, proposal: int, won: int},
     *   revenue: array{mrr: float, arr: float, renewals_due: int, outstanding: float},
     *   clients_by_industry: array<string, int>,
     *   client_growth: array{labels: list<string>, values: list<int>},
     *   activity: list<array{text: string, meta: string, at: \Carbon\CarbonInterface}>
     * }
     */
    public function enterpriseDashboard(Consultant $consultant, Collection $engagements, bool $needsRenewal): array
    {
        $companyIds = $engagements
            ->map(fn ($e) => $e->managedCompany?->id)
            ->filter()
            ->values()
            ->all();

        $pendingReviews = empty($companyIds)
            ? 0
            : Measurement::query()
                ->whereIn('status', ['submitted', 'draft'])
                ->whereHas('location', fn ($q) => $q->whereIn('company_id', $companyIds))
                ->count();

        $intros = $consultant->introRequests()->get();
        $pipeline = [
            'new_leads' => $intros->where('status', 'new')->count(),
            'qualified' => $intros->where('status', 'contacted')->filter(fn ($i) => blank($i->pack_type))->count(),
            'proposal' => $intros->where('status', 'contacted')->filter(fn ($i) => filled($i->pack_type))->count(),
            'won' => $intros->where('status', 'converted')->count(),
        ];

        $orders = $consultant->orders()->get();
        $monthStart = now()->startOfMonth();

        $monthlyRevenue = (float) $orders
            ->filter(fn ($o) => $o->order_status === 'completed'
                && $o->completed_at
                && $o->completed_at->gte($monthStart))
            ->sum('payout_aed');

        if ($monthlyRevenue <= 0) {
            $monthlyRevenue = (float) $orders
                ->where('order_status', 'completed')
                ->sum('payout_aed') / max(1, 12);
        }

        $mrr = round($monthlyRevenue, 2);
        $outstanding = (float) $orders
            ->whereIn('escrow_status', ['pending_payment', 'held'])
            ->sum('amount_aed');

        $clientsByIndustry = [];
        foreach ($engagements as $engagement) {
            $sector = $engagement->managedCompany?->sector ?: 'Other';
            $clientsByIndustry[$sector] = ($clientsByIndustry[$sector] ?? 0) + 1;
        }
        arsort($clientsByIndustry);

        $growthLabels = [];
        $growthValues = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $growthLabels[] = $month->format('M');
            $growthValues[] = $engagements
                ->filter(fn ($e) => $e->created_at && $e->created_at->format('Y-m') === $key)
                ->count();
        }

        $activity = collect();

        foreach ($consultant->introRequests()->with('company')->latest()->limit(4)->get() as $intro) {
            $activity->push([
                'text' => 'Intro request from ' . ($intro->company?->name ?? 'prospect'),
                'meta' => ucfirst(str_replace('_', ' ', $intro->status)),
                'at' => $intro->created_at,
            ]);
        }

        if (!empty($companyIds)) {
            $recentMeasurements = Measurement::query()
                ->with('location.company')
                ->whereHas('location', fn ($q) => $q->whereIn('company_id', $companyIds))
                ->latest()
                ->limit(6)
                ->get();

            foreach ($recentMeasurements as $measurement) {
                $companyName = $measurement->location?->company?->name ?? 'Client';
                $activity->push([
                    'text' => "{$companyName}: " . ($measurement->location?->name ?? 'Location') . ' entry',
                    'meta' => ucfirst($measurement->status) . ' · ' . co2e_t($measurement->total_co2e ?? 0) . ' tCO₂e',
                    'at' => $measurement->created_at,
                ]);
            }
        }

        $activity = $activity
            ->sortByDesc(fn ($item) => $item['at']?->timestamp ?? 0)
            ->take(8)
            ->values()
            ->all();

        return [
            'pending_reviews' => $pendingReviews,
            'monthly_revenue' => $mrr,
            'pipeline' => $pipeline,
            'revenue' => [
                'mrr' => $mrr,
                'arr' => round($mrr * 12, 2),
                'renewals_due' => $needsRenewal ? 1 : 0,
                'outstanding' => round($outstanding, 2),
            ],
            'clients_by_industry' => $clientsByIndustry,
            'client_growth' => [
                'labels' => $growthLabels,
                'values' => $growthValues,
            ],
            'activity' => $activity,
        ];
    }
}
