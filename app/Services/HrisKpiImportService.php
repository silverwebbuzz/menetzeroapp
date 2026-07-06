<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EsgKpiSnapshot;
use App\Models\HrisKpiImportLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class HrisKpiImportService
{
    public function __construct(
        protected EsgScorecardService $scorecardService,
    ) {
    }

    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return ['fiscal_year', 'metric_key', 'value', 'unit', 'source_system', 'notes'];
    }

    public function templateCsv(): string
    {
        $lines = [implode(',', $this->templateHeaders())];

        foreach (config('hris_kpi_import.template_examples', []) as $row) {
            $lines[] = implode(',', array_map(
                fn ($cell) => '"' . str_replace('"', '""', (string) $cell) . '"',
                $row
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>, log_id: int|null}
     */
    public function importFromCsv(Company $company, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read file.'], 'log_id' => null];
        }

        $header = fgetcsv($handle);
        if (!$header || array_map('strtolower', array_map('trim', $header)) !== $this->templateHeaders()) {
            fclose($handle);

            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Invalid HRIS CSV header. Download the HRIS template and use exact column names.'],
                'log_id' => null,
            ];
        }

        $metricMap = $this->manualMetricMap();
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;
        $maxRows = (int) config('hris_kpi_import.max_rows', 500);
        $sourceSystem = null;
        $fiscalYears = [];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($imported + $skipped >= $maxRows) {
                $errors[] = "Stopped at row limit ({$maxRows}). Split file and import again.";
                break;
            }

            if (count($row) < 3 || trim($row[0] ?? '') === '') {
                $skipped++;

                continue;
            }

            $fiscalYear = (int) $row[0];
            $metricKey = trim($row[1]);
            $value = trim($row[2] ?? '');
            $unit = trim($row[3] ?? '');
            $rowSource = trim($row[4] ?? '');
            $notes = trim($row[5] ?? '');

            if ($rowSource !== '') {
                $sourceSystem = $sourceSystem ?? $rowSource;
            }

            if (!isset($metricMap[$metricKey])) {
                $errors[] = "Line {$line}: unknown metric_key \"{$metricKey}\" (enterprise manual KPIs only).";
                $skipped++;

                continue;
            }

            $category = $metricMap[$metricKey]['category'];
            $metric = $metricMap[$metricKey]['metric'];

            if ($value === '') {
                EsgKpiSnapshot::where('company_id', $company->id)
                    ->where('fiscal_year', $fiscalYear)
                    ->where('metric_key', $metricKey)
                    ->where('source', EsgKpiSnapshot::SOURCE_HRIS)
                    ->delete();
                $skipped++;

                continue;
            }

            if (!is_numeric($value)) {
                $errors[] = "Line {$line}: value must be numeric.";
                $skipped++;

                continue;
            }

            EsgKpiSnapshot::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'fiscal_year' => $fiscalYear,
                    'metric_key' => $metricKey,
                ],
                [
                    'category' => $category,
                    'value' => (float) $value,
                    'unit' => $unit ?: ($metric['unit'] ?? ''),
                    'source' => EsgKpiSnapshot::SOURCE_HRIS,
                ]
            );

            $fiscalYears[$fiscalYear] = true;
            $imported++;
            unset($notes);
        }

        fclose($handle);

        $log = HrisKpiImportLog::create([
            'company_id' => $company->id,
            'fiscal_year' => count($fiscalYears) === 1 ? array_key_first($fiscalYears) : null,
            'imported_by' => Auth::id(),
            'filename' => $file->getClientOriginalName(),
            'source_system' => $sourceSystem,
            'rows_imported' => $imported,
            'rows_skipped' => $skipped,
            'errors' => $errors ?: null,
        ]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'log_id' => $log->id,
        ];
    }

    /**
     * @return array<string, array{category: string, metric: array<string, mixed>}>
     */
    protected function manualMetricMap(): array
    {
        $map = [];

        foreach ($this->scorecardService->mergedCategoriesConfig() as $categoryKey => $category) {
            foreach ($category['metrics'] as $metricKey => $metric) {
                if (($metric['source'] ?? '') !== 'manual') {
                    continue;
                }
                $map[$metricKey] = [
                    'category' => $categoryKey,
                    'metric' => $metric,
                ];
            }
        }

        return $map;
    }
}
