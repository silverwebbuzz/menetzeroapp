<?php

namespace App\Services;

use App\Models\ClimateOpportunity;
use App\Models\ClimateRisk;
use App\Models\CompanyDisclosure;
use App\Models\ReductionTarget;
use Illuminate\Support\Facades\Auth;

class DisclosureService
{
    public function sectionConfig(string $section): ?array
    {
        return config("disclosure.ifrs_s2.sections.{$section}");
    }

    public function validSections(): array
    {
        return array_keys(config('disclosure.ifrs_s2.sections', []));
    }

    public function getSection(int $companyId, int $fiscalYear, string $section): CompanyDisclosure
    {
        return CompanyDisclosure::firstOrCreate(
            [
                'company_id' => $companyId,
                'framework' => 'ifrs_s2',
                'section' => $section,
                'fiscal_year' => $fiscalYear,
            ],
            ['content' => [], 'status' => 'draft']
        );
    }

    public function saveSection(int $companyId, int $fiscalYear, string $section, array $content): CompanyDisclosure
    {
        $config = $this->sectionConfig($section);
        if (!$config) {
            abort(404, 'Unknown disclosure section.');
        }

        $filtered = [];
        foreach ($config['fields'] as $key => $field) {
            if (array_key_exists($key, $content)) {
                $filtered[$key] = $content[$key];
            }
        }

        $complete = $this->sectionFieldsComplete($section, $filtered);

        $record = CompanyDisclosure::updateOrCreate(
            [
                'company_id' => $companyId,
                'framework' => 'ifrs_s2',
                'section' => $section,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'content' => $filtered,
                'status' => $complete ? 'complete' : 'draft',
                'last_edited_by' => Auth::id(),
            ]
        );

        return $record;
    }

    public function sectionFieldsComplete(string $section, array $content): bool
    {
        $config = $this->sectionConfig($section);
        if (!$config) {
            return false;
        }

        foreach ($config['fields'] as $key => $field) {
            if (!empty($field['required']) && empty(trim((string) ($content[$key] ?? '')))) {
                return false;
            }
        }

        return true;
    }

    public function completeness(int $companyId, int $fiscalYear): array
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
            $items[$section] = ['complete' => $done, 'weight' => $w, 'label' => $this->sectionConfig($section)['title'] ?? $section];
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

        $percent = $totalWeight > 0 ? (int) round(($earned / $totalWeight) * 100) : 0;

        return [
            'percent' => $percent,
            'items' => $items,
            'earned' => $earned,
            'total_weight' => $totalWeight,
        ];
    }
}
