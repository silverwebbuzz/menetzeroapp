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
}
