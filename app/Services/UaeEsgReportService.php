<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\CompanyReportingSetting;

/**
 * Aggregates existing MenetZero modules into a single UAE ESG Report payload.
 *
 * GHG figures always come from GhgReportService / IfrsS2ReportService — never duplicated here.
 *
 * @see documentation/UAE_ESG_REPORT_GAP_ANALYSIS_AND_ROADMAP.md
 */
class UaeEsgReportService
{
    public function __construct(
        protected GhgReportService $ghgReportService,
        protected IfrsS2ReportService $s2ReportService,
        protected IfrsS1ReportService $s1ReportService,
        protected GriReportService $griReportService,
        protected GriContentIndexService $griContentIndexService,
        protected DisclosureService $disclosureService,
        protected EsgDashboardService $esgDashboardService,
    ) {
    }

    /**
     * Build the full UAE ESG report data structure for preview / PDF export.
     */
    public function build(Company $company, int $fiscalYear): array
    {
        $esgDisclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'esg_report')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $s2 = $this->s2ReportService->build($company, $fiscalYear);
        $s1 = $this->s1ReportService->build($company, $fiscalYear, includeS2: false);
        $gri = $this->griReportService->build($company, $fiscalYear);
        $dashboard = $this->esgDashboardService->build($company, $fiscalYear);

        $reportingSettings = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        return [
            'framework' => config('esg_report.framework_label', 'UAE ESG Report'),
            'company' => $company,
            'fiscal_year' => $fiscalYear,
            'generated_at' => now()->format('d M Y'),
            'reporting_settings' => $reportingSettings,
            'frameworks_disclosed' => config('esg_report.frameworks_disclosed', []),

            // Narrative chapters (framework: esg_report)
            'narrative' => $this->narrativeSections($esgDisclosures),

            // Quantitative & structured — delegated (single source of truth)
            'ghg' => $s2['ghg'],
            'ghg_methodology' => $s2['methodology'],
            'ifrs_s2' => [
                'completeness' => $s2['completeness'],
                'governance' => $s2['governance'],
                'strategy' => $s2['strategy'],
                'risk_management' => $s2['risk_management'],
                'climate_risks' => $s2['climate_risks'],
                'climate_opportunities' => $s2['climate_opportunities'],
                'reduction_targets' => $s2['reduction_targets'],
            ],
            'ifrs_s1' => [
                'completeness' => $s1['completeness'],
                'governance' => $s1['governance'],
                'strategy' => $s1['strategy'],
                'risk_management' => $s1['risk_management'],
                'material_topics' => $s1['material_topics'],
            ],
            'gri' => [
                'completeness' => $gri['completeness'],
                'gri_305' => $gri['gri_305'],
                'material_topics' => $gri['material_topics'],
                'material_topics_process' => $gri['material_topics_process'],
                'general' => $gri['general'],
                'energy' => $gri['energy'],
                'water' => $gri['water'],
                'waste' => $gri['waste'],
                'social_hr' => $gri['social_hr'],
                'diversity' => $gri['diversity'],
            ],

            // Indexes & mappings
            'gri_content_index' => $this->griContentIndexService->build($company, $fiscalYear),
            'ifrs_s2_index' => $this->buildDisclosureIndex('ifrs_s2_index', $s2['completeness'], $s2['ghg']['has_data'] ?? false),
            'ifrs_s1_index' => $this->buildDisclosureIndex('ifrs_s1_index', $s1['completeness']),
            'sdg_map' => $this->buildSdgMapping($gri['material_topics']),

            // E/S/G completeness dashboard (not yet full scorecard)
            'esg_dashboard' => $dashboard,
            'completeness' => $this->completeness($company->id, $fiscalYear, $esgDisclosures, $s2, $gri),

            'section_config' => config('esg_report.sections', []),
            'disclaimer' => 'This report is prepared using MENetZero as a working draft. '
                . 'Narrative content is the responsibility of the reporting entity. '
                . 'GHG figures are calculated from entered activity data. '
                . 'Official MOCCAE submission must be completed at mrv.ae using IEQT.',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, CompanyDisclosure>  $esgDisclosures
     */
    protected function narrativeSections($esgDisclosures): array
    {
        $sections = [];
        foreach (config('esg_report.sections', []) as $key => $config) {
            $sections[$key] = [
                'title' => $config['title'],
                'content' => $esgDisclosures->get($key)?->content ?? [],
            ];
        }

        return $sections;
    }

    protected function buildDisclosureIndex(string $configKey, array $completeness, bool $ghgHasData = false): array
    {
        $rows = config("esg_report.{$configKey}", []);
        $items = $completeness['items'] ?? [];

        return collect($rows)->map(function (array $row) use ($items, $ghgHasData) {
            $section = $row['section'] ?? null;
            $complete = match ($section) {
                'ghg' => $ghgHasData,
                default => $section && ($items[$section]['complete'] ?? false),
            };

            $location = '—';
            if ($complete) {
                $location = $section === 'ghg'
                    ? 'MENetZero — GHG Inventory / Quick Input'
                    : 'MENetZero disclosures — ' . ($items[$section]['label'] ?? $section);
            }

            return array_merge($row, [
                'status' => $complete ? 'Reported' : 'Not reported / incomplete',
                'report_location' => $location,
            ]);
        })->values()->all();
    }

    protected function buildSdgMapping($materialTopics): array
    {
        $sdgConfig = config('esg_report.sdg_map', []);
        $topics = collect($materialTopics)->filter(fn ($t) => $t['is_material'] ?? false);

        $rows = [];
        foreach ($sdgConfig as $topicKey => $meta) {
            $material = $topics->contains(fn ($t) => str_contains(strtolower($t['key'] ?? ''), $topicKey)
                || str_contains(strtolower($t['label'] ?? ''), $topicKey));

            $rows[] = [
                'topic_key' => $topicKey,
                'sdg_goals' => $meta['goals'],
                'sdg_label' => $meta['label'],
                'material' => $material,
            ];
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, CompanyDisclosure>  $esgDisclosures
     */
    protected function completeness(
        int $companyId,
        int $fiscalYear,
        $esgDisclosures,
        array $s2,
        array $gri,
    ): array {
        $weights = config('esg_report.completeness_weights', []);
        $labels = [
            'about_report' => 'About This Report',
            'leadership_message' => 'Message from Leadership',
            'about_company' => 'About the Company',
            'esg_strategy' => 'ESG Strategy',
            'materiality' => 'Materiality Assessment',
            'ghg_inventory' => 'GHG Inventory',
            'ifrs_s2_climate' => 'Climate Risk (IFRS S2)',
            'gri_index' => 'GRI Content Index',
        ];
        $items = [];
        $totalWeight = array_sum($weights) ?: 100;
        $earned = 0;

        foreach ($weights as $key => $weight) {
            $complete = match ($key) {
                'about_report' => $this->sectionComplete($esgDisclosures, 'about_report'),
                'leadership_message' => $this->sectionComplete($esgDisclosures, 'leadership_message'),
                'about_company' => $this->sectionComplete($esgDisclosures, 'about_company'),
                'esg_strategy' => $this->sectionComplete($esgDisclosures, 'esg_strategy'),
                'materiality' => $gri['completeness']['items']['material_topics_process']['complete'] ?? false,
                'ghg_inventory' => (bool) ($s2['ghg']['has_data'] ?? false),
                'ifrs_s2_climate' => ($s2['completeness']['percent'] ?? 0) >= 50,
                'gri_index' => ($gri['completeness']['percent'] ?? 0) >= 40,
                default => false,
            };

            $items[$key] = [
                'complete' => $complete,
                'weight' => $weight,
                'label' => $labels[$key] ?? $key,
            ];
            if ($complete) {
                $earned += $weight;
            }
        }

        return [
            'percent' => (int) round(($earned / $totalWeight) * 100),
            'items' => $items,
        ];
    }

    protected function sectionComplete($esgDisclosures, string $section): bool
    {
        $content = $esgDisclosures->get($section)?->content ?? [];
        if (empty($content)) {
            return false;
        }

        $config = config("esg_report.sections.{$section}.fields", []);
        foreach ($config as $fieldKey => $field) {
            if (!empty($field['required']) && empty(trim((string) ($content[$fieldKey] ?? '')))) {
                return false;
            }
        }

        return true;
    }
}
