<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyReportingSetting;
use App\Models\EsgKpiSnapshot;

class SasbIndexService
{
    public function __construct(
        protected IfrsS2ReportService $s2ReportService,
        protected DisclosureService $disclosureService,
    ) {
    }

    public function sectorForCompany(Company $company, int $fiscalYear): ?string
    {
        return CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->value('sasb_sector');
    }

    public function saveSector(int $companyId, int $fiscalYear, ?string $sector): void
    {
        if ($sector && !isset(config('sasb.sectors')[$sector])) {
            abort(422, 'Invalid SASB sector.');
        }

        $settings = CompanyReportingSetting::firstOrCreate(
            ['company_id' => $companyId, 'fiscal_year' => $fiscalYear],
            [
                'organisational_boundary' => 'operational_control',
                'consolidation_approach' => 'operational_control',
                'gwp_version' => 'AR6',
                'scope3_category_policy' => CompanyReportingSetting::defaultScope3Policy(),
            ]
        );

        $settings->update(['sasb_sector' => $sector ?: null]);
    }

    public function build(Company $company, int $fiscalYear): array
    {
        $sector = $this->sectorForCompany($company, $fiscalYear);
        if (!$sector) {
            return [
                'sector' => null,
                'sector_label' => null,
                'metrics' => [],
            ];
        }

        $sectorConfig = config("sasb.sectors.{$sector}");
        $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];
        $gri = $this->disclosureService->griSectionsContent($company->id, $fiscalYear);
        $manual = EsgKpiSnapshot::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->where('source', EsgKpiSnapshot::SOURCE_MANUAL)
            ->get()
            ->keyBy('metric_key');

        $rows = [];
        foreach ($sectorConfig['metrics'] ?? [] as $code => $metric) {
            [$value, $status] = $this->resolveMetric($metric, $ghg, $gri, $manual);
            $rows[] = [
                'code' => $code,
                'label' => $metric['label'],
                'unit' => $metric['unit'],
                'value' => $value,
                'status' => $status,
                'source' => $metric['source'],
            ];
        }

        return [
            'sector' => $sector,
            'sector_label' => $sectorConfig['label'] ?? $sector,
            'industry' => $sectorConfig['industry'] ?? '',
            'metrics' => $rows,
        ];
    }

    public function toCsv(array $index): string
    {
        $lines = ['SASB Code,Metric,Unit,Value,Status,Data source'];
        foreach ($index['metrics'] ?? [] as $row) {
            $lines[] = implode(',', [
                $this->csvCell($row['code']),
                $this->csvCell($row['label']),
                $this->csvCell($row['unit']),
                $this->csvCell($row['value'] ?? '—'),
                $this->csvCell($row['status']),
                $this->csvCell($row['source']),
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    protected function resolveMetric(array $metric, array $ghg, array $gri, $manual): array
    {
        if ($metric['source'] === 'ghg') {
            if (!($ghg['has_data'] ?? false)) {
                return [null, 'Not reported'];
            }
            $val = match ($metric['field'] ?? '') {
                'scope1' => $ghg['scope_tonnes']['Scope 1'] ?? null,
                'scope2' => $ghg['scope_tonnes']['Scope 2'] ?? null,
                'scope2_location' => $ghg['scope2_location_tonnes'] ?? null,
                'scope3' => $ghg['scope_tonnes']['Scope 3'] ?? null,
                'total' => $ghg['total_tonnes'] ?? null,
                default => null,
            };

            return [$val !== null ? (string) round((float) $val, 4) : null, $val !== null ? 'Reported' : 'Not reported'];
        }

        if ($metric['source'] === 'gri') {
            $raw = $gri[$metric['section'] ?? ''][$metric['field'] ?? ''] ?? null;
            if ($raw === '' || $raw === null) {
                return [null, 'Not reported'];
            }

            return [(string) $raw, 'Reported'];
        }

        if ($metric['source'] === 'manual') {
            $snap = $manual->get($metric['metric_key'] ?? '');
            if (!$snap || $snap->value === null) {
                return [null, 'Not reported'];
            }

            return [(string) $snap->value, 'Reported'];
        }

        return [null, 'Omitted'];
    }

    protected function csvCell(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}
