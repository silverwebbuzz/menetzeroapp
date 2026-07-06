<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EsgKpiSnapshot;
use Illuminate\Http\UploadedFile;

class EsgScorecardImportService
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
        return ['fiscal_year', 'category', 'metric_key', 'value', 'unit'];
    }

    public function templateCsv(): string
    {
        $headers = implode(',', $this->templateHeaders());
        $examples = [
            '2024,social,community_investment_aed,50000,AED',
            '2024,governance,supplier_audits,12,count',
            '2024,environment,sasb_tr_mt_air_emissions,150,metric tonnes',
        ];

        return $headers . "\n" . implode("\n", $examples);
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importFromCsv(Company $company, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read file.']];
        }

        $header = fgetcsv($handle);
        if (!$header || array_map('strtolower', array_map('trim', $header)) !== $this->templateHeaders()) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Invalid CSV header. Download the template and use exact column names.']];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;
        $categories = config('esg_scorecard.categories', []);

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count($row) < 4 || trim($row[0] ?? '') === '') {
                $skipped++;

                continue;
            }

            $fiscalYear = (int) $row[0];
            $category = trim($row[1]);
            $metricKey = trim($row[2]);
            $value = trim($row[3] ?? '');
            $unit = trim($row[4] ?? '');

            if (!isset($categories[$category]['metrics'][$metricKey])) {
                $errors[] = "Line {$line}: unknown category/metric ({$category}/{$metricKey}).";
                $skipped++;

                continue;
            }

            $metric = $categories[$category]['metrics'][$metricKey];
            if (($metric['source'] ?? '') !== 'manual') {
                $errors[] = "Line {$line}: {$metricKey} is auto-linked ({$metric['source']}) — use GRI or Quick Input instead.";
                $skipped++;

                continue;
            }

            if ($value === '') {
                EsgKpiSnapshot::where('company_id', $company->id)
                    ->where('fiscal_year', $fiscalYear)
                    ->where('metric_key', $metricKey)
                    ->where('source', EsgKpiSnapshot::SOURCE_MANUAL)
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
                    'source' => EsgKpiSnapshot::SOURCE_MANUAL,
                ]
            );
            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }
}
