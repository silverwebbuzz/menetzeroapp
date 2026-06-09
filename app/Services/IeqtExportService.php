<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyReportingSetting;
use App\Models\Measurement;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prepares GHG inventory data for transfer to MOCCAE IEQT (mrv.ae).
 * Official legal submission still happens on the national platform.
 */
class IeqtExportService
{
    public function __construct(protected GhgReportService $reportService)
    {
    }

    public function buildRows(Measurement $measurement): array
    {
        $measurement->load(['location.company']);
        $report = $this->reportService->build($measurement);
        $company = $measurement->location->company;
        $settings = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $measurement->fiscal_year)
            ->first();

        $header = [
            'company_name',
            'location_name',
            'fiscal_year',
            'reporting_period',
            'organisational_boundary',
            'consolidation_approach',
            'base_year',
            'gwp_version',
            'scope',
            'source',
            'activity',
            'quantity',
            'unit',
            'emission_factor',
            'factor_unit',
            'methodology',
            'factor_source',
            'co2e_kg',
            'co2e_tonnes',
            'scope2_method',
            'is_biogenic',
            'entry_date',
        ];

        $rows = [$header];

        foreach ($report['activity_register'] as $row) {
            $rows[] = [
                $company->name,
                $measurement->location->name,
                $measurement->fiscal_year,
                $report['reporting_period'],
                $settings?->organisational_boundary ?? 'operational_control',
                $settings?->consolidation_approach ?? 'operational_control',
                $settings?->base_year ?? '',
                $settings?->gwp_version ?? 'AR6',
                $row['scope'],
                $row['source'],
                $row['activity'],
                $row['quantity'],
                $row['unit'],
                $row['factor_value'],
                $row['factor_unit'],
                $row['methodology'],
                $row['reference'],
                $row['kg'],
                $row['tonnes'],
                $row['scope2_method'] ?? '',
                !empty($row['is_biogenic']) ? 'yes' : 'no',
                $row['entry_date'],
            ];
        }

        $rows[] = [];
        $rows[] = ['SUMMARY'];
        $rows[] = ['Scope 1 tCO2e', $report['scope_tonnes']['Scope 1'] ?? 0];
        $rows[] = ['Scope 2 location-based tCO2e', $report['scope2_location_tonnes'] ?? ($report['scope_tonnes']['Scope 2'] ?? 0)];
        if (isset($report['scope2_market_tonnes'])) {
            $rows[] = ['Scope 2 market-based tCO2e', $report['scope2_market_tonnes']];
        }
        $rows[] = ['Scope 3 tCO2e', $report['scope_tonnes']['Scope 3'] ?? 0];
        $rows[] = ['Total tCO2e', $report['total_tonnes']];

        return $rows;
    }

    public function downloadCsv(Company $company, int $locationId, int $fiscalYear): StreamedResponse
    {
        $measurement = Measurement::with('location')
            ->where('fiscal_year', $fiscalYear)
            ->where('location_id', $locationId)
            ->whereHas('location', fn ($q) => $q->where('company_id', $company->id))
            ->firstOrFail();

        $rows = $this->buildRows($measurement);
        $filename = sprintf('ieqt-export-%s-%s-%s.csv', $company->id, $locationId, $fiscalYear);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
