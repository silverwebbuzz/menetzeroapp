<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;

class GriContentIndexService
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected IfrsS2ReportService $s2ReportService,
    ) {
    }

    public function build(Company $company, int $fiscalYear): array
    {
        $disclosures = CompanyDisclosure::where('company_id', $company->id)
            ->where('framework', 'gri')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section');

        $materialTopics = collect($this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear))
            ->filter(fn ($t) => $t['is_material']);
        $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];

        $rows = [];
        $crosswalk = config('gri_crosswalk', []);
        foreach (config('disclosure.gri.content_index', []) as $code => $meta) {
            $walk = $crosswalk[$code] ?? [];
            $rows[] = [
                'code' => $code,
                'title' => $meta['title'],
                'status' => $this->resolveStatus($meta, $disclosures, $materialTopics, $ghg),
                'location' => $this->resolveLocation($meta),
                'ungc' => $walk['ungc'] ?? '—',
                'wef' => $walk['wef'] ?? '—',
                'sdg' => $walk['sdg'] ?? '—',
            ];
        }

        return $rows;
    }

    public function toCsv(Company $company, int $fiscalYear, bool $extended = false): string
    {
        $rows = $this->build($company, $fiscalYear);
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

    public function toExtendedCsv(Company $company, int $fiscalYear): string
    {
        return $this->toCsv($company, $fiscalYear, true);
    }

    protected function resolveStatus(array $meta, $disclosures, $materialTopics, array $ghg): string
    {
        if (($meta['source'] ?? '') === 'material_topics') {
            return $materialTopics->isNotEmpty() ? 'Reported' : 'Not reported';
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
