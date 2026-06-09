<?php

namespace App\Services;

use App\Models\ClimateOpportunity;
use App\Models\ClimateRisk;
use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\CompanyReportingSetting;
use App\Models\Measurement;
use App\Models\ReductionTarget;

class IfrsS2ReportService
{
    public function __construct(
        protected GhgReportService $ghgReportService,
        protected DisclosureService $disclosureService,
    ) {
    }

    public function build(Company $company, int $fiscalYear): array
    {
        $disclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'ifrs_s2')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $ghg = $this->aggregateGhg($company, $fiscalYear);
        $completeness = $this->disclosureService->completenessS2($company->id, $fiscalYear);

        return [
            'framework' => 'IFRS S2 Climate-related Disclosures',
            'company' => $company,
            'fiscal_year' => $fiscalYear,
            'generated_at' => now()->format('d M Y'),
            'completeness' => $completeness,
            'governance' => $disclosures->get('governance')?->content ?? [],
            'strategy' => $disclosures->get('strategy')?->content ?? [],
            'risk_management' => $disclosures->get('risk_management')?->content ?? [],
            'climate_risks' => ClimateRisk::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)->orderBy('risk_type')->get(),
            'climate_opportunities' => ClimateOpportunity::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)->orderBy('name')->get(),
            'reduction_targets' => ReductionTarget::where('company_id', $company->id)
                ->with('transitionActions')
                ->where('status', 'active')
                ->orderBy('target_year')
                ->get(),
            'ghg' => $ghg,
            'reporting_settings' => CompanyReportingSetting::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->first(),
            'section_config' => config('disclosure.ifrs_s2.sections'),
            'methodology' => $this->ghgReportService->methodologyStatement(),
        ];
    }

    protected function aggregateGhg(Company $company, int $fiscalYear): array
    {
        $measurements = Measurement::whereHas('location', fn ($q) => $q->where('company_id', $company->id))
            ->where('fiscal_year', $fiscalYear)
            ->get();

        $scope1 = (float) $measurements->sum('scope_1_co2e');
        $scope2 = (float) $measurements->sum('scope_2_co2e');
        $scope3 = (float) $measurements->sum('scope_3_co2e');
        $total = (float) $measurements->sum('total_co2e');

        $scope3Categories = collect();
        foreach ($measurements as $m) {
            foreach ($this->ghgReportService->buildScope3Categories($m) as $row) {
                $key = $row['category'];
                if ($scope3Categories->has($key)) {
                    $existing = $scope3Categories->get($key);
                    $existing['kg'] += $row['kg'];
                    $existing['tonnes'] = GhgReportService::kgToTonnes($existing['kg']);
                    $existing['entry_count'] = ($existing['entry_count'] ?? 0) + ($row['entry_count'] ?? 0);
                    $scope3Categories->put($key, $existing);
                } else {
                    $scope3Categories->put($key, $row);
                }
            }
        }

        $locationKg = 0.0;
        $marketKg = 0.0;
        foreach ($measurements as $m) {
            $split = $this->ghgReportService->buildScope2Split($m);
            $locationKg += $split['location_kg'];
            $marketKg += $split['market_kg'];
        }
        if ($marketKg <= 0) {
            $marketKg = $locationKg;
        }

        return [
            'scope_kg' => ['Scope 1' => $scope1, 'Scope 2' => $scope2, 'Scope 3' => $scope3],
            'scope_tonnes' => [
                'Scope 1' => GhgReportService::kgToTonnes($scope1),
                'Scope 2' => GhgReportService::kgToTonnes($scope2),
                'Scope 3' => GhgReportService::kgToTonnes($scope3),
            ],
            'total_kg' => $total,
            'total_tonnes' => GhgReportService::kgToTonnes($total),
            'scope2_location_tonnes' => GhgReportService::kgToTonnes($locationKg),
            'scope2_market_tonnes' => GhgReportService::kgToTonnes($marketKg),
            'scope_3_categories' => $scope3Categories->sortByDesc('kg')->values(),
            'location_count' => $measurements->pluck('location_id')->unique()->count(),
            'has_data' => $total > 0,
        ];
    }
}
