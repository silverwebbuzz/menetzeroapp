<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\MaterialSustainabilityTopic;
use App\Models\SustainabilityRisk;

class IfrsS1ReportService
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected IfrsS2ReportService $s2ReportService,
    ) {
    }

    public function build(Company $company, int $fiscalYear, bool $includeS2 = true): array
    {
        $disclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'ifrs_s1')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $completeness = $this->disclosureService->completenessS1($company->id, $fiscalYear);
        $materialTopics = collect($this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear))
            ->filter(fn ($t) => $t['is_material'])
            ->values();

        $report = [
            'framework' => 'IFRS S1 General Sustainability-related Disclosures',
            'company' => $company,
            'fiscal_year' => $fiscalYear,
            'generated_at' => now()->format('d M Y'),
            'completeness' => $completeness,
            'governance' => $disclosures->get('governance')?->content ?? [],
            'strategy' => $disclosures->get('strategy')?->content ?? [],
            'risk_management' => $disclosures->get('risk_management')?->content ?? [],
            'material_topics' => $materialTopics,
            'all_topics' => $this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear),
            'sustainability_risks' => SustainabilityRisk::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->orderBy('topic')
                ->orderBy('name')
                ->get(),
            'section_config' => config('disclosure.ifrs_s1.sections'),
            'include_s2' => false,
            's2_report' => null,
        ];

        if ($includeS2 && $this->disclosureService->hasS2Data($company->id, $fiscalYear)) {
            $report['include_s2'] = true;
            $report['s2_report'] = $this->s2ReportService->build($company, $fiscalYear);
        }

        return $report;
    }
}
