<?php

namespace App\Services;

use App\Models\ClimateOpportunity;
use App\Models\ClimateRisk;
use App\Models\CompanyDisclosure;
use App\Models\MaterialSustainabilityTopic;
use App\Models\ReductionTarget;
use App\Models\SustainabilityRisk;
use Illuminate\Support\Facades\Auth;

class DisclosureService
{
    public function sectionConfig(string $framework, string $section): ?array
    {
        if ($framework === 'esg_report') {
            return config("esg_report.sections.{$section}");
        }

        return config("disclosure.{$framework}.sections.{$section}");
    }

    public function validSections(string $framework): array
    {
        if ($framework === 'esg_report') {
            return array_keys(config('esg_report.sections', []));
        }

        return array_keys(config("disclosure.{$framework}.sections", []));
    }

    public function getSection(int $companyId, int $fiscalYear, string $section, string $framework = 'ifrs_s2'): CompanyDisclosure
    {
        return CompanyDisclosure::firstOrCreate(
            [
                'company_id' => $companyId,
                'framework' => $framework,
                'section' => $section,
                'fiscal_year' => $fiscalYear,
            ],
            ['content' => [], 'status' => 'draft']
        );
    }

    public function saveSection(int $companyId, int $fiscalYear, string $section, array $content, string $framework = 'ifrs_s2'): CompanyDisclosure
    {
        $config = $this->sectionConfig($framework, $section);
        if (!$config) {
            abort(404, 'Unknown disclosure section.');
        }

        $filtered = [];
        foreach ($config['fields'] as $key => $field) {
            if (array_key_exists($key, $content)) {
                $filtered[$key] = $content[$key];
            }
        }

        if ($framework === 'esg_report' && $section === 'about_report') {
            $existing = CompanyDisclosure::where('company_id', $companyId)
                ->where('framework', 'esg_report')
                ->where('section', 'about_report')
                ->where('fiscal_year', $fiscalYear)
                ->value('content');

            if (is_array($existing) && !empty($existing['assurance_document'])) {
                $filtered['assurance_document'] = $existing['assurance_document'];
            }
        }

        $complete = $this->sectionFieldsComplete($framework, $section, $filtered);

        return CompanyDisclosure::updateOrCreate(
            [
                'company_id' => $companyId,
                'framework' => $framework,
                'section' => $section,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'content' => $filtered,
                'status' => $complete ? 'complete' : 'draft',
                'last_edited_by' => Auth::id(),
            ]
        );
    }

    public function sectionFieldsComplete(string $framework, string $section, array $content): bool
    {
        $config = $this->sectionConfig($framework, $section);
        if (!$config) {
            return false;
        }

        foreach ($config['fields'] as $key => $field) {
            if (empty($field['required'])) {
                continue;
            }
            $val = $content[$key] ?? '';
            if (($field['type'] ?? '') === 'number') {
                if ($val === '' || $val === null) {
                    return false;
                }
            } elseif (empty(trim((string) $val))) {
                return false;
            }
        }

        return true;
    }

    public function completeness(int $companyId, int $fiscalYear, string $framework = 'ifrs_s2'): array
    {
        return match ($framework) {
            'ifrs_s1' => $this->completenessS1($companyId, $fiscalYear),
            'gri' => $this->completenessGri($companyId, $fiscalYear),
            default => $this->completenessS2($companyId, $fiscalYear),
        };
    }

    public function completenessS2(int $companyId, int $fiscalYear): array
    {
        $weights = config('disclosure.ifrs_s2.completeness_weights', []);
        $items = [];
        $totalWeight = array_sum($weights);
        $earned = 0;

        foreach (['governance', 'strategy', 'risk_management'] as $section) {
            $record = CompanyDisclosure::where('company_id', $companyId)
                ->where('framework', 'ifrs_s2')
                ->where('section', $section)
                ->where('fiscal_year', $fiscalYear)
                ->first();

            $done = $record && $record->status === 'complete';
            $w = $weights[$section] ?? 0;
            if ($done) {
                $earned += $w;
            }
            $items[$section] = [
                'complete' => $done,
                'weight' => $w,
                'label' => $this->sectionConfig('ifrs_s2', $section)['title'] ?? $section,
            ];
        }

        $riskCount = ClimateRisk::where('company_id', $companyId)->where('fiscal_year', $fiscalYear)->count();
        $riskDone = $riskCount > 0;
        $w = $weights['climate_risks'] ?? 0;
        if ($riskDone) {
            $earned += $w;
        }
        $items['climate_risks'] = ['complete' => $riskDone, 'weight' => $w, 'label' => 'Climate Risk Register', 'count' => $riskCount];

        $oppCount = ClimateOpportunity::where('company_id', $companyId)->where('fiscal_year', $fiscalYear)->count();
        $oppDone = $oppCount > 0;
        $w = $weights['climate_opportunities'] ?? 0;
        if ($oppDone) {
            $earned += $w;
        }
        $items['climate_opportunities'] = ['complete' => $oppDone, 'weight' => $w, 'label' => 'Climate Opportunities', 'count' => $oppCount];

        $targetCount = ReductionTarget::where('company_id', $companyId)->where('status', 'active')->count();
        $targetDone = $targetCount > 0;
        $w = $weights['reduction_targets'] ?? 0;
        if ($targetDone) {
            $earned += $w;
        }
        $items['reduction_targets'] = ['complete' => $targetDone, 'weight' => $w, 'label' => 'Reduction Targets', 'count' => $targetCount];

        return $this->completenessResult($items, $earned, $totalWeight);
    }

    public function completenessS1(int $companyId, int $fiscalYear): array
    {
        $weights = config('disclosure.ifrs_s1.completeness_weights', []);
        $items = [];
        $totalWeight = array_sum($weights);
        $earned = 0;

        foreach (['governance', 'strategy', 'risk_management'] as $section) {
            $record = CompanyDisclosure::where('company_id', $companyId)
                ->where('framework', 'ifrs_s1')
                ->where('section', $section)
                ->where('fiscal_year', $fiscalYear)
                ->first();

            $done = $record && $record->status === 'complete';
            $w = $weights[$section] ?? 0;
            if ($done) {
                $earned += $w;
            }
            $items[$section] = [
                'complete' => $done,
                'weight' => $w,
                'label' => $this->sectionConfig('ifrs_s1', $section)['title'] ?? $section,
            ];
        }

        $materialCount = MaterialSustainabilityTopic::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->where('is_material', true)
            ->count();
        $materialDone = $materialCount > 0;
        $w = $weights['material_topics'] ?? 0;
        if ($materialDone) {
            $earned += $w;
        }
        $items['material_topics'] = [
            'complete' => $materialDone,
            'weight' => $w,
            'label' => 'Material Topics',
            'count' => $materialCount,
        ];

        $riskCount = SustainabilityRisk::where('company_id', $companyId)->where('fiscal_year', $fiscalYear)->count();
        $riskDone = $riskCount > 0;
        $w = $weights['sustainability_risks'] ?? 0;
        if ($riskDone) {
            $earned += $w;
        }
        $items['sustainability_risks'] = [
            'complete' => $riskDone,
            'weight' => $w,
            'label' => 'Sustainability Risk Register',
            'count' => $riskCount,
        ];

        return $this->completenessResult($items, $earned, $totalWeight);
    }

    public function syncMaterialTopics(int $companyId, int $fiscalYear, array $topics): void
    {
        $catalog = config('disclosure.ifrs_s1.material_topics', []);

        foreach ($catalog as $key => $meta) {
            $row = $topics[$key] ?? [];
            $isMaterial = !empty($row['is_material']);

            MaterialSustainabilityTopic::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'topic_key' => $key,
                ],
                [
                    'is_material' => $isMaterial,
                    'rationale' => $isMaterial
                        ? ($row['rationale'] ?? null)
                        : ($row['rationale'] ?? 'Not material for this reporting period'),
                ]
            );
        }
    }

    public function syncMaterialityMatrix(int $companyId, int $fiscalYear, array $topics): void
    {
        $catalog = config('disclosure.ifrs_s1.material_topics', []);

        foreach ($catalog as $key => $meta) {
            $row = $topics[$key] ?? [];
            $impact = $row['impact_materiality'] ?? null;
            $financial = $row['financial_materiality'] ?? null;
            $isMaterial = !empty($row['is_material'])
                || in_array($impact, ['medium', 'high'], true)
                || in_array($financial, ['medium', 'high'], true);

            MaterialSustainabilityTopic::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'topic_key' => $key,
                ],
                [
                    'is_material' => $isMaterial,
                    'impact_materiality' => in_array($impact, ['low', 'medium', 'high'], true) ? $impact : null,
                    'financial_materiality' => in_array($financial, ['low', 'medium', 'high'], true) ? $financial : null,
                    'rationale' => $row['rationale'] ?? ($isMaterial ? null : 'Not material for this reporting period'),
                ]
            );
        }
    }

    public function materialTopicsForCompany(int $companyId, int $fiscalYear): array
    {
        $catalog = config('disclosure.ifrs_s1.material_topics', []);
        $saved = MaterialSustainabilityTopic::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('topic_key');

        $rows = [];
        foreach ($catalog as $key => $meta) {
            $record = $saved->get($key);
            $rows[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'gri' => $meta['gri'] ?? null,
                'is_material' => (bool) ($record->is_material ?? false),
                'rationale' => $record->rationale ?? '',
                'impact_materiality' => $record->impact_materiality ?? '',
                'financial_materiality' => $record->financial_materiality ?? '',
            ];
        }

        return $rows;
    }

    public function completenessGri(int $companyId, int $fiscalYear): array
    {
        $weights = config('disclosure.gri.completeness_weights', []);
        $items = [];
        $earned = 0;

        foreach (['material_topics_process', 'general', 'energy', 'water', 'waste', 'social_hr', 'diversity', 'health_safety', 'supply_chain', 'governance_metrics'] as $section) {
            $record = CompanyDisclosure::where('company_id', $companyId)
                ->where('framework', 'gri')
                ->where('section', $section)
                ->where('fiscal_year', $fiscalYear)
                ->first();

            $done = $record && $record->status === 'complete';
            $w = $weights[$section] ?? 0;
            if ($done) {
                $earned += $w;
            }
            $items[$section] = [
                'complete' => $done,
                'weight' => $w,
                'label' => $this->sectionConfig('gri', $section)['title'] ?? $section,
            ];
        }

        $materialCount = MaterialSustainabilityTopic::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->where('is_material', true)
            ->count();
        $materialDone = $materialCount > 0;
        $w = $weights['material_topics'] ?? 0;
        if ($materialDone) {
            $earned += $w;
        }
        $items['material_topics'] = [
            'complete' => $materialDone,
            'weight' => $w,
            'label' => 'GRI 3 Material Topics List',
            'count' => $materialCount,
        ];

        $ghgDone = $this->hasGhgData($companyId, $fiscalYear);
        $w = $weights['gri_305'] ?? 0;
        if ($ghgDone) {
            $earned += $w;
        }
        $items['gri_305'] = [
            'complete' => $ghgDone,
            'weight' => $w,
            'label' => 'GRI 305 Emissions (auto from GHG inventory)',
        ];

        return $this->completenessResult($items, $earned, array_sum($weights));
    }

    public function hasGhgData(int $companyId, int $fiscalYear): bool
    {
        return \App\Models\Measurement::where('fiscal_year', $fiscalYear)
            ->whereHas('location', fn ($q) => $q->where('company_id', $companyId))
            ->where('total_co2e', '>', 0)
            ->exists();
    }

    public function griSectionsContent(int $companyId, int $fiscalYear): array
    {
        return CompanyDisclosure::where('company_id', $companyId)
            ->where('framework', 'gri')
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section')
            ->map(fn ($r) => $r->content ?? [])
            ->all();
    }

    public function hasS2Data(int $companyId, int $fiscalYear): bool
    {
        return CompanyDisclosure::where('company_id', $companyId)
            ->where('framework', 'ifrs_s2')
            ->where('fiscal_year', $fiscalYear)
            ->exists()
            || ClimateRisk::where('company_id', $companyId)->where('fiscal_year', $fiscalYear)->exists();
    }

    protected function completenessResult(array $items, int $earned, int $totalWeight): array
    {
        $percent = $totalWeight > 0 ? (int) round(($earned / $totalWeight) * 100) : 0;

        return [
            'percent' => $percent,
            'items' => $items,
            'earned' => $earned,
            'total_weight' => $totalWeight,
        ];
    }
}
