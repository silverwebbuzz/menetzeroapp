<?php

namespace App\Services;

use App\Models\Company;

class EsgDashboardService
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected IfrsS2ReportService $s2ReportService,
    ) {
    }

    public function build(Company $company, int $fiscalYear): array
    {
        $s2 = $this->disclosureService->completenessS2($company->id, $fiscalYear);
        $s1 = $this->disclosureService->completenessS1($company->id, $fiscalYear);
        $gri = $this->disclosureService->completenessGri($company->id, $fiscalYear);
        $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];
        $griContent = $this->disclosureService->griSectionsContent($company->id, $fiscalYear);

        $environmental = $this->scoreEnvironmental($gri, $ghg, $griContent);
        $social = $this->scoreSocial($gri, $griContent);
        $governance = $this->scoreGovernance($s2, $s1, $gri, $griContent);

        return [
            'fiscal_year' => $fiscalYear,
            'overall' => (int) round(($environmental['percent'] + $social['percent'] + $governance['percent']) / 3),
            'environmental' => $environmental,
            'social' => $social,
            'governance' => $governance,
            'frameworks' => [
                'ifrs_s2' => $s2,
                'ifrs_s1' => $s1,
                'gri' => $gri,
            ],
            'ghg_summary' => [
                'total_tonnes' => $ghg['total_tonnes'] ?? 0,
                'scope1' => $ghg['scope_tonnes']['Scope 1'] ?? 0,
                'scope2' => $ghg['scope_tonnes']['Scope 2'] ?? 0,
                'scope3' => $ghg['scope_tonnes']['Scope 3'] ?? 0,
                'has_data' => $ghg['has_data'] ?? false,
            ],
        ];
    }

    protected function scoreEnvironmental(array $gri, array $ghg, array $griContent): array
    {
        $checks = [
            ['label' => 'GHG inventory data', 'done' => $ghg['has_data'] ?? false],
            ['label' => 'GRI 305 emissions mapped', 'done' => $gri['items']['gri_305']['complete'] ?? false],
            ['label' => 'GRI 302 Energy', 'done' => !empty($griContent['energy']['total_energy_gj'])],
            ['label' => 'GRI 303 Water', 'done' => !empty($griContent['water']['withdrawal_total_m3'])],
            ['label' => 'GRI 306 Waste', 'done' => !empty($griContent['waste']['waste_non_hazardous_tonnes']) || !empty($griContent['waste']['waste_total_tonnes'])],
        ];
        $done = collect($checks)->where('done', true)->count();

        return [
            'percent' => (int) round(($done / max(count($checks), 1)) * 100),
            'checks' => $checks,
            'label' => 'Environmental (E)',
        ];
    }

    protected function scoreSocial(array $gri, array $griContent): array
    {
        $checks = [
            ['label' => 'GRI 401–404 Employment', 'done' => !empty($griContent['social_hr']['employees_total'])],
            ['label' => 'GRI 404 Training hours', 'done' => !empty($griContent['social_hr']['training_hours_avg'])],
            ['label' => 'GRI 405 Diversity', 'done' => isset($griContent['diversity']['women_management_percent']) && $griContent['diversity']['women_management_percent'] !== ''],
            ['label' => 'Material social topics', 'done' => $gri['items']['material_topics']['complete'] ?? false],
        ];
        $done = collect($checks)->where('done', true)->count();

        return [
            'percent' => (int) round(($done / max(count($checks), 1)) * 100),
            'checks' => $checks,
            'label' => 'Social (S)',
        ];
    }

    protected function scoreGovernance(array $s2, array $s1, array $gri, array $griContent): array
    {
        $checks = [
            ['label' => 'IFRS S2 governance', 'done' => $s2['items']['governance']['complete'] ?? false],
            ['label' => 'IFRS S1 governance', 'done' => $s1['items']['governance']['complete'] ?? false],
            ['label' => 'GRI 2 General disclosures', 'done' => $gri['items']['general']['complete'] ?? false],
            ['label' => 'GRI 3 material topics process', 'done' => $gri['items']['material_topics_process']['complete'] ?? false],
            ['label' => 'Stakeholder engagement', 'done' => !empty($griContent['general']['stakeholder_engagement'])],
        ];
        $done = collect($checks)->where('done', true)->count();

        return [
            'percent' => (int) round(($done / max(count($checks), 1)) * 100),
            'checks' => $checks,
            'label' => 'Governance (G)',
        ];
    }
}
