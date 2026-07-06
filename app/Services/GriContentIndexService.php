<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\StakeholderEngagement;

class GriContentIndexService
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected IfrsS2ReportService $s2ReportService,
    ) {
    }

    /**
     * Growth-tier index — base disclosures only (unchanged contract).
     */
    public function build(Company $company, int $fiscalYear): array
    {
        return $this->buildFromConfig(
            $company,
            $fiscalYear,
            config('disclosure.gri.content_index', []),
            config('gri_crosswalk', []),
        );
    }

    /**
     * Enterprise-tier index — base + enterprise extension rows.
     */
    public function buildEnterprise(Company $company, int $fiscalYear): array
    {
        $base = config('disclosure.gri.content_index', []);
        $extra = config('gri_content_index_enterprise', []);

        return $this->buildFromConfig(
            $company,
            $fiscalYear,
            array_merge($base, $extra),
            array_merge(config('gri_crosswalk', []), config('gri_crosswalk_enterprise', [])),
        );
    }

    public function toCsv(Company $company, int $fiscalYear, bool $extended = false): string
    {
        return $this->rowsToCsv($this->build($company, $fiscalYear), $extended);
    }

    public function toExtendedCsv(Company $company, int $fiscalYear): string
    {
        return $this->toCsv($company, $fiscalYear, true);
    }

    public function toEnterpriseCsv(Company $company, int $fiscalYear, bool $extended = true): string
    {
        return $this->rowsToCsv($this->buildEnterprise($company, $fiscalYear), $extended);
    }

    /**
     * @param  array<string, array<string, mixed>>  $indexConfig
     * @param  array<string, array<string, string>>  $crosswalk
     * @return list<array<string, string>>
     */
    protected function buildFromConfig(Company $company, int $fiscalYear, array $indexConfig, array $crosswalk): array
    {
        $disclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'gri')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $esgDisclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'esg_report')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $stakeholderCount = StakeholderEngagement::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->count();

        $materialTopics = collect($this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear))
            ->filter(fn ($t) => $t['is_material']);
        $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];

        $rows = [];
        foreach ($indexConfig as $code => $meta) {
            $walk = $crosswalk[$code] ?? [];
            $rows[] = [
                'code' => $code,
                'title' => $meta['title'],
                'status' => $this->resolveStatus(
                    $meta,
                    $disclosures,
                    $esgDisclosures,
                    $materialTopics,
                    $ghg,
                    $stakeholderCount,
                ),
                'location' => $this->resolveLocation($meta),
                'ungc' => $walk['ungc'] ?? '—',
                'wef' => $walk['wef'] ?? '—',
                'sdg' => $walk['sdg'] ?? '—',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    protected function rowsToCsv(array $rows, bool $extended): string
    {
        if ($extended) {
            $lines = ['GRI Standard,Disclosure,Status,Report location,UNGC,WEF SCM,UN SDG'];
            foreach ($rows as $row) {
                $lines[] = implode(',', [
                    $this->csvCell($row['code']),
                    $this->csvCell($row['title']),
                    $this->csvCell($row['status']),
                    $this->csvCell($row['location']),
                    $this->csvCell($row['ungc'] ?? '—'),
                    $this->csvCell($row['wef'] ?? '—'),
                    $this->csvCell($row['sdg'] ?? '—'),
                ]);
            }
        } else {
            $lines = ['GRI Standard,Disclosure,Status,Report location'];
            foreach ($rows as $row) {
                $lines[] = implode(',', [
                    $this->csvCell($row['code']),
                    $this->csvCell($row['title']),
                    $this->csvCell($row['status']),
                    $this->csvCell($row['location']),
                ]);
            }
        }

        return implode("\n", $lines);
    }

    protected function resolveStatus(
        array $meta,
        $disclosures,
        $esgDisclosures,
        $materialTopics,
        array $ghg,
        int $stakeholderCount,
    ): string {
        if (($meta['source'] ?? '') === 'material_topics') {
            return $materialTopics->isNotEmpty() ? 'Reported' : 'Not reported';
        }

        if (($meta['source'] ?? '') === 'stakeholder_register') {
            return $stakeholderCount > 0 ? 'Reported' : 'Not reported';
        }

        if (($meta['source'] ?? '') === 'materiality_matrix') {
            $complete = $materialTopics->filter(
                fn ($t) => !empty($t['impact_materiality']) && !empty($t['financial_materiality'])
            )->isNotEmpty();

            return $complete ? 'Reported' : 'Not reported';
        }

        if (($meta['source'] ?? '') === 'esg_report') {
            $section = $meta['section'] ?? null;
            $field = $meta['field'] ?? null;
            if (!$section || !$field) {
                return 'Omitted';
            }
            $content = $esgDisclosures->get($section)?->content ?? [];
            $val = $content[$field] ?? null;

            return ($val !== null && $val !== '') ? 'Reported' : 'Not reported';
        }

        if (($meta['source'] ?? '') === 'gri_305') {
            if (!($ghg['has_data'] ?? false)) {
                return 'Not reported';
            }

            return match ($meta['metric'] ?? '') {
                'scope1' => ($ghg['scope_tonnes']['Scope 1'] ?? 0) > 0 ? 'Reported' : 'Not reported',
                'scope2' => ($ghg['scope2_location_tonnes'] ?? 0) > 0 ? 'Reported' : 'Not reported',
                'scope3' => ($ghg['scope_tonnes']['Scope 3'] ?? 0) > 0 ? 'Reported' : 'Partial',
                default => 'Reported',
            };
        }

        $section = $meta['section'] ?? null;
        $field = $meta['field'] ?? null;
        if (!$section || !$field) {
            return 'Omitted';
        }

        $content = $disclosures->get($section)?->content ?? [];
        $val = $content[$field] ?? null;

        return ($val !== null && $val !== '') ? 'Reported' : 'Not reported';
    }

    protected function resolveLocation(array $meta): string
    {
        if (($meta['source'] ?? '') === 'gri_305') {
            return 'GRI 305 — auto from GHG inventory';
        }
        if (($meta['source'] ?? '') === 'material_topics') {
            return 'GRI 3 — Material topics';
        }
        if (($meta['source'] ?? '') === 'stakeholder_register') {
            return 'ESG Depth — Stakeholder register';
        }
        if (($meta['source'] ?? '') === 'materiality_matrix') {
            return 'ESG Depth — Materiality matrix';
        }
        if (($meta['source'] ?? '') === 'esg_report') {
            $section = $meta['section'] ?? '';

            return config("esg_report.sections.{$section}.title", 'UAE ESG Report');
        }
        if (!empty($meta['section'])) {
            return config("disclosure.gri.sections.{$meta['section']}.title", $meta['section']);
        }

        return 'GRI report';
    }

    protected function csvCell(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}
