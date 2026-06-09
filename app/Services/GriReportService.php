<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\ReductionTarget;

class GriReportService
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected IfrsS2ReportService $s2ReportService,
        protected GriContentIndexService $contentIndexService,
    ) {
    }

    public function build(Company $company, int $fiscalYear): array
    {
        $disclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'gri')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];
        $materialTopics = collect($this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear))
            ->filter(fn ($t) => $t['is_material'])
            ->values();

        $targets = ReductionTarget::where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('target_year')
            ->get();

        $intensity = $this->calculateIntensity($ghg, $disclosures->get('energy')?->content ?? []);

        return [
            'framework' => 'GRI Sustainability Report',
            'company' => $company,
            'fiscal_year' => $fiscalYear,
            'generated_at' => now()->format('d M Y'),
            'completeness' => $this->disclosureService->completenessGri($company->id, $fiscalYear),
            'material_topics' => $materialTopics,
            'material_topics_process' => $disclosures->get('material_topics_process')?->content ?? [],
            'general' => $disclosures->get('general')?->content ?? [],
            'energy' => $disclosures->get('energy')?->content ?? [],
            'water' => $disclosures->get('water')?->content ?? [],
            'waste' => $disclosures->get('waste')?->content ?? [],
            'social_hr' => $disclosures->get('social_hr')?->content ?? [],
            'diversity' => $disclosures->get('diversity')?->content ?? [],
            'gri_305' => [
                'scope1_tonnes' => $ghg['scope_tonnes']['Scope 1'] ?? 0,
                'scope2_location_tonnes' => $ghg['scope2_location_tonnes'] ?? 0,
                'scope2_market_tonnes' => $ghg['scope2_market_tonnes'] ?? 0,
                'scope3_tonnes' => $ghg['scope_tonnes']['Scope 3'] ?? 0,
                'total_tonnes' => $ghg['total_tonnes'] ?? 0,
                'intensity' => $intensity,
                'scope_3_categories' => $ghg['scope_3_categories'] ?? collect(),
                'has_data' => $ghg['has_data'] ?? false,
                'reduction_targets' => $targets,
            ],
            'section_config' => config('disclosure.gri.sections'),
            'content_index' => $this->contentIndexService->build($company, $fiscalYear),
        ];
    }

    protected function calculateIntensity(array $ghg, array $energy): array
    {
        $total = (float) ($ghg['total_tonnes'] ?? 0);
        $denom = $energy['energy_intensity_denominator'] ?? null;
        $manual = isset($energy['energy_intensity_value']) && $energy['energy_intensity_value'] !== ''
            ? (float) $energy['energy_intensity_value']
            : null;

        return [
            'value' => $manual,
            'denominator' => $denom,
            'ghg_per_denominator' => ($manual && $denom) ? null : ($denom && $total > 0 ? round($total, 4) . ' tCO₂e / ' . $denom : null),
        ];
    }
}
