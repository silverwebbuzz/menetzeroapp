<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\EsgKpiSnapshot;
use App\Models\EsgSustainabilityTarget;
use App\Models\StakeholderEngagement;

class EsgScorecardService
{
    public function __construct(
        protected IfrsS2ReportService $s2ReportService,
        protected DisclosureService $disclosureService,
        protected EnergyFromActivityService $energyFromActivityService,
    ) {
    }

    /**
     * Growth-tier scorecard — base metrics only (unchanged contract).
     *
     * @return array{fiscal_year: int, years: int[], categories: array<string, array{title: string, rows: array}>}
     */
    public function build(Company $company, int $fiscalYear): array
    {
        return $this->buildFromCategories(
            $company,
            $fiscalYear,
            config('esg_scorecard.categories', []),
        );
    }

    /**
     * Enterprise-tier scorecard — base + enterprise extension metrics.
     *
     * @return array{fiscal_year: int, years: int[], categories: array<string, array{title: string, rows: array}>}
     */
    public function buildEnterprise(Company $company, int $fiscalYear): array
    {
        return $this->buildFromCategories(
            $company,
            $fiscalYear,
            $this->mergedCategoriesConfig(),
        );
    }

    /**
     * @return array<string, array{title: string, metrics: array<string, array<string, mixed>>}>
     */
    public function mergedCategoriesConfig(): array
    {
        return $this->mergeCategoryMetrics(
            config('esg_scorecard.categories', []),
            config('esg_scorecard_enterprise.categories', []),
        );
    }

    /**
     * @return array<string, array{title: string, metrics: array<string, array<string, mixed>>}>
     */
    public function baseCategoriesConfig(): array
    {
        return config('esg_scorecard.categories', []);
    }

    /**
     * Persist manual KPI values for a fiscal year (base + enterprise manual metrics).
     *
     * @param  array<string, string|float|null>  $metrics
     */
    public function saveManual(int $companyId, int $fiscalYear, string $category, array $metrics): void
    {
        $categoryMetrics = config("esg_scorecard.categories.{$category}.metrics", []);

        foreach ($metrics as $metricKey => $rawValue) {
            if (!isset($categoryMetrics[$metricKey]) || $categoryMetrics[$metricKey]['source'] !== 'manual') {
                continue;
            }

            $metric = $categoryMetrics[$metricKey];
            $value = is_string($rawValue) ? trim($rawValue) : $rawValue;

            if ($value === '' || $value === null) {
                EsgKpiSnapshot::where('company_id', $companyId)
                    ->where('fiscal_year', $fiscalYear)
                    ->where('metric_key', $metricKey)
                    ->where('source', EsgKpiSnapshot::SOURCE_MANUAL)
                    ->delete();

                continue;
            }

            $existing = EsgKpiSnapshot::where('company_id', $companyId)
                ->where('fiscal_year', $fiscalYear)
                ->where('metric_key', $metricKey)
                ->first();

            if ($existing && $existing->source === EsgKpiSnapshot::SOURCE_HRIS) {
                continue;
            }

            EsgKpiSnapshot::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'metric_key' => $metricKey,
                ],
                [
                    'category' => $category,
                    'value' => (float) $value,
                    'unit' => $metric['unit'],
                    'source' => EsgKpiSnapshot::SOURCE_MANUAL,
                ]
            );
        }
    }

    /**
     * Cache auto-resolved GHG/GRI values as snapshots (base metrics only).
     */
    public function syncAutoSnapshots(Company $company, int $fiscalYear): int
    {
        return $this->syncSnapshotsForConfig($company, $fiscalYear, config('esg_scorecard.categories', []));
    }

    /**
     * Cache auto-resolved values including enterprise-linked metrics.
     */
    public function syncEnterpriseAutoSnapshots(Company $company, int $fiscalYear): int
    {
        return $this->syncSnapshotsForConfig($company, $fiscalYear, $this->mergedCategoriesConfig());
    }

    /**
     * Flat rows for Excel export.
     *
     * @return list<array{category: string, metric: string, unit: string, source: string, y1: mixed, y2: mixed, y3: mixed}>
     */
    public function flattenForExport(array $scorecard): array
    {
        $years = $scorecard['years'];
        $rows = [];

        foreach ($scorecard['categories'] as $category) {
            foreach ($category['rows'] as $row) {
                $rows[] = [
                    'category' => $category['title'],
                    'metric' => $row['label'],
                    'unit' => $row['unit'],
                    'source' => $row['source'],
                    (string) $years[0] => $this->formatValue($row['values'][$years[0]] ?? null, $row['decimals']),
                    (string) $years[1] => $this->formatValue($row['values'][$years[1]] ?? null, $row['decimals']),
                    (string) $years[2] => $this->formatValue($row['values'][$years[2]] ?? null, $row['decimals']),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, array{title: string, metrics: array<string, array<string, mixed>>}>  $categories
     * @return array{fiscal_year: int, years: int[], categories: array}
     */
    protected function buildFromCategories(Company $company, int $fiscalYear, array $categories): array
    {
        $years = [$fiscalYear - 2, $fiscalYear - 1, $fiscalYear];
        $manualSnapshots = $this->loadManualSnapshots($company->id, $years);
        $builtCategories = [];

        foreach ($categories as $categoryKey => $category) {
            $rows = [];
            foreach ($category['metrics'] as $metricKey => $metric) {
                $values = [];
                $sources = [];
                foreach ($years as $year) {
                    [$values[$year], $sources[$year]] = $this->resolveMetric(
                        $company,
                        $year,
                        $categoryKey,
                        $metricKey,
                        $metric,
                        $manualSnapshots,
                    );
                }

                $rows[] = [
                    'key' => $metricKey,
                    'label' => $metric['label'],
                    'unit' => $metric['unit'],
                    'source' => $metric['source'],
                    'editable' => $metric['source'] === 'manual',
                    'decimals' => $metric['decimals'] ?? 2,
                    'values' => $values,
                    'value_sources' => $sources,
                ];
            }

            $builtCategories[$categoryKey] = [
                'title' => $category['title'],
                'rows' => $rows,
            ];
        }

        return [
            'fiscal_year' => $fiscalYear,
            'years' => $years,
            'categories' => $builtCategories,
        ];
    }

    /**
     * @param  array<string, array{title: string, metrics: array<string, array<string, mixed>>}>  $categories
     */
    protected function syncSnapshotsForConfig(Company $company, int $fiscalYear, array $categories): int
    {
        $years = [$fiscalYear - 2, $fiscalYear - 1, $fiscalYear];
        $manualSnapshots = $this->loadManualSnapshots($company->id, $years);
        $written = 0;

        foreach ($years as $year) {
            foreach ($categories as $categoryKey => $category) {
                foreach ($category['metrics'] as $metricKey => $metric) {
                    if ($metric['source'] === 'manual') {
                        continue;
                    }

                    [$value] = $this->resolveMetric(
                        $company,
                        $year,
                        $categoryKey,
                        $metricKey,
                        $metric,
                        $manualSnapshots,
                    );

                    if ($value === null) {
                        EsgKpiSnapshot::where('company_id', $company->id)
                            ->where('fiscal_year', $year)
                            ->where('metric_key', $metricKey)
                            ->where('source', EsgKpiSnapshot::SOURCE_AUTO)
                            ->delete();

                        continue;
                    }

                    EsgKpiSnapshot::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'fiscal_year' => $year,
                            'metric_key' => $metricKey,
                        ],
                        [
                            'category' => $categoryKey,
                            'value' => $value,
                            'unit' => $metric['unit'],
                            'source' => EsgKpiSnapshot::SOURCE_AUTO,
                        ]
                    );
                    $written++;
                }
            }
        }

        return $written;
    }

    /**
     * @param  array<string, array{title: string, metrics: array<string, mixed>}>  $base
     * @param  array<string, array{title: string, metrics: array<string, mixed>}>  $extra
     * @return array<string, array{title: string, metrics: array<string, mixed>}>
     */
    protected function mergeCategoryMetrics(array $base, array $extra): array
    {
        foreach ($extra as $catKey => $cat) {
            if (!isset($base[$catKey])) {
                $base[$catKey] = $cat;

                continue;
            }

            $base[$catKey]['metrics'] = array_merge(
                $base[$catKey]['metrics'] ?? [],
                $cat['metrics'] ?? [],
            );
        }

        return $base;
    }

    /**
     * @param  array<int, array<string, EsgKpiSnapshot>>  $manualSnapshots
     * @return array{0: ?float, 1: string}
     */
    protected function resolveMetric(
        Company $company,
        int $fiscalYear,
        string $categoryKey,
        string $metricKey,
        array $metric,
        array $manualSnapshots,
    ): array {
        if ($metric['source'] === 'manual') {
            $snapshot = $manualSnapshots[$fiscalYear][$metricKey] ?? null;

            return [
                $snapshot?->value !== null ? (float) $snapshot->value : null,
                $snapshot?->source === EsgKpiSnapshot::SOURCE_HRIS ? 'hris' : 'manual',
            ];
        }

        if ($metric['source'] === 'stakeholder_count') {
            $count = StakeholderEngagement::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->count();

            return [$count > 0 ? (float) $count : null, 'register'];
        }

        if ($metric['source'] === 'esg_target_count') {
            $count = EsgSustainabilityTarget::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->count();

            return [$count > 0 ? (float) $count : null, 'targets'];
        }

        if ($metric['source'] === 'ghg') {
            $ghg = $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];
            if (!($ghg['has_data'] ?? false)) {
                return [null, 'ghg'];
            }

            $value = match ($metric['field']) {
                'scope1' => $ghg['scope_tonnes']['Scope 1'] ?? null,
                'scope2' => $ghg['scope_tonnes']['Scope 2'] ?? null,
                'scope2_location' => $ghg['scope2_location_tonnes'] ?? null,
                'scope2_market' => $ghg['scope2_market_tonnes'] ?? null,
                'scope3' => $ghg['scope_tonnes']['Scope 3'] ?? null,
                'total' => $ghg['total_tonnes'] ?? null,
                default => null,
            };

            return [$value !== null ? (float) $value : null, 'ghg'];
        }

        if ($metric['source'] === 'gri') {
            $content = $this->disclosureService->griSectionsContent($company->id, $fiscalYear);
            $section = $metric['section'] ?? '';
            $field = $metric['field'] ?? '';
            $raw = $content[$section][$field] ?? null;

            if ($raw === '' || $raw === null) {
                return [null, 'gri'];
            }

            return [(float) $raw, 'gri'];
        }

        if ($metric['source'] === 'esg_report') {
            $section = $metric['section'] ?? '';
            $field = $metric['field'] ?? '';
            $content = CompanyDisclosure::where('company_id', $company->id)
                ->where('framework', 'esg_report')
                ->where('fiscal_year', $fiscalYear)
                ->where('section', $section)
                ->value('content') ?? [];
            $raw = $content[$field] ?? null;

            if ($raw === '' || $raw === null) {
                return [null, 'esg_report'];
            }

            return [(float) $raw, 'esg_report'];
        }

        if ($metric['source'] === 'energy_activity') {
            $gj = $this->energyFromActivityService->totalGj($company->id, $fiscalYear);

            return [$gj, 'energy_activity'];
        }

        return [null, 'unknown'];
    }

    /**
     * @param  int[]  $years
     * @return array<int, array<string, EsgKpiSnapshot>>
     */
    protected function loadManualSnapshots(int $companyId, array $years): array
    {
        $records = EsgKpiSnapshot::where('company_id', $companyId)
            ->whereIn('fiscal_year', $years)
            ->whereIn('source', [EsgKpiSnapshot::SOURCE_MANUAL, EsgKpiSnapshot::SOURCE_HRIS])
            ->get();

        $indexed = [];
        foreach ($years as $year) {
            $indexed[$year] = [];
        }

        foreach ($records as $record) {
            $indexed[$record->fiscal_year][$record->metric_key] = $record;
        }

        return $indexed;
    }

    protected function formatValue(mixed $value, int $decimals): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, $decimals);
    }
}
