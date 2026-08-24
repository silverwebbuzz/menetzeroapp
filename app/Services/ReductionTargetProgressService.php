<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ReductionTarget;
use Illuminate\Support\Collection;

/**
 * Compares reduction targets against actual GHG inventory for a fiscal year.
 *
 * The targets themselves are captured under Disclosures → Targets; this service
 * is the only place that pairs them with actuals, so the ESG dashboard and the
 * targets list report identical numbers.
 */
class ReductionTargetProgressService
{
    public function __construct(
        protected IfrsS2ReportService $s2ReportService,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $ghg  Pre-built GHG block. Callers that
     *         already hold one (the ESG dashboard) should pass it — building the
     *         IFRS S2 report is several queries and need not run twice per page.
     */

    /**
     * Scopes counted for each scope_coverage option on ReductionTarget.
     */
    protected const COVERAGE_SCOPES = [
        'scope1' => ['Scope 1'],
        'scope2' => ['Scope 2'],
        'scope12' => ['Scope 1', 'Scope 2'],
        'scope3' => ['Scope 3'],
        'scope123' => ['Scope 1', 'Scope 2', 'Scope 3'],
    ];

    /**
     * Active targets for a company, each with progress against $fiscalYear actuals.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(Company $company, int $fiscalYear, ?array $ghg = null): array
    {
        $targets = ReductionTarget::where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('target_year')
            ->get();

        if ($targets->isEmpty()) {
            return [];
        }

        $ghg ??= $this->s2ReportService->build($company, $fiscalYear)['ghg'] ?? [];

        return $targets
            ->map(fn (ReductionTarget $target) => $this->progressFor($target, $ghg, $fiscalYear))
            ->all();
    }

    /**
     * The single most relevant target for a headline summary: the active target
     * with the nearest target_year that has not yet passed. Falls back to the
     * last target so an all-past set still shows something rather than nothing.
     *
     * @param  array<int, array<string, mixed>>  $progress
     * @return array<string, mixed>|null
     */
    public function nextTarget(array $progress): ?array
    {
        if ($progress === []) {
            return null;
        }

        $upcoming = array_values(array_filter($progress, fn ($p) => !$p['is_past_due']));

        return $upcoming[0] ?? $progress[array_key_last($progress)];
    }

    /**
     * @param  array<string, mixed>  $ghg
     * @return array<string, mixed>
     */
    protected function progressFor(ReductionTarget $target, array $ghg, int $fiscalYear): array
    {
        $current = $this->actualForCoverage($target->scope_coverage, $ghg);
        $baseline = $target->baseline_tco2e !== null ? (float) $target->baseline_tco2e : null;
        $targetValue = $this->resolvedTargetTonnes($target, $baseline);

        $remaining = ($current !== null && $targetValue !== null)
            ? max(0.0, $current - $targetValue)
            : null;

        return [
            'id' => $target->id,
            'name' => $target->name,
            'target_year' => (int) $target->target_year,
            'base_year' => $target->base_year !== null ? (int) $target->base_year : null,
            'scope_coverage' => $target->scope_coverage,
            'scope_label' => ReductionTarget::SCOPE_COVERAGE[$target->scope_coverage] ?? $target->scope_coverage,
            'sbti_aligned' => (bool) $target->sbti_aligned,
            'target_type' => $target->target_type,
            'baseline_tco2e' => $baseline,
            'target_tco2e' => $targetValue,
            // True when target_tco2e was absent and we derived it from percent.
            'target_is_derived' => $targetValue !== null && $target->target_tco2e === null,
            'current_tco2e' => $current,
            'current_year' => $fiscalYear,
            'remaining_tco2e' => $remaining,
            'reduction_percent' => $target->reduction_percent !== null ? (float) $target->reduction_percent : null,
            'achieved_percent' => $this->achievedPercent($baseline, $targetValue, $current),
            'change_vs_baseline_percent' => $this->changeVsBaseline($baseline, $current),
            'is_past_due' => (int) $target->target_year < $fiscalYear,
            'status' => $this->status($target, $baseline, $targetValue, $current, $fiscalYear),
        ];
    }

    /**
     * Sum only the scopes a target actually covers — comparing a Scope 1 & 2
     * target against a total that includes Scope 3 would overstate the gap.
     */
    protected function actualForCoverage(string $coverage, array $ghg): ?float
    {
        if (!($ghg['has_data'] ?? false)) {
            return null;
        }

        $scopes = self::COVERAGE_SCOPES[$coverage] ?? self::COVERAGE_SCOPES['scope12'];
        $tonnes = $ghg['scope_tonnes'] ?? [];

        $total = 0.0;
        $found = false;

        foreach ($scopes as $scope) {
            if (isset($tonnes[$scope]) && $tonnes[$scope] !== null) {
                $total += (float) $tonnes[$scope];
                $found = true;
            }
        }

        return $found ? $total : null;
    }

    /**
     * target_tco2e is nullable: a percent-only target still has an absolute
     * equivalent whenever a baseline exists, so derive it rather than hiding
     * the target from the comparison entirely.
     */
    protected function resolvedTargetTonnes(ReductionTarget $target, ?float $baseline): ?float
    {
        if ($target->target_tco2e !== null) {
            return (float) $target->target_tco2e;
        }

        if ($baseline !== null && $target->reduction_percent !== null) {
            return $baseline * (1 - ((float) $target->reduction_percent / 100));
        }

        return null;
    }

    /**
     * Share of the required reduction achieved so far, 0–100.
     */
    protected function achievedPercent(?float $baseline, ?float $targetValue, ?float $current): ?int
    {
        if ($baseline === null || $targetValue === null || $current === null) {
            return null;
        }

        $required = $baseline - $targetValue;

        if ($required <= 0) {
            return null; // target at or above baseline — no reduction implied
        }

        $achieved = ($baseline - $current) / $required * 100;

        return (int) round(max(0, min(100, $achieved)));
    }

    protected function changeVsBaseline(?float $baseline, ?float $current): ?float
    {
        if ($baseline === null || $current === null || $baseline <= 0) {
            return null;
        }

        return round(($current - $baseline) / $baseline * 100, 1);
    }

    /**
     * On/off track against the straight line from base_year to target_year:
     * being 30% reduced in 2026 is ahead for a 2035 target and far behind for
     * a 2027 one, so raw progress alone cannot answer this.
     *
     * @return array{key: string, label: string}
     */
    protected function status(
        ReductionTarget $target,
        ?float $baseline,
        ?float $targetValue,
        ?float $current,
        int $fiscalYear,
    ): array {
        if ($current === null) {
            return ['key' => 'no_data', 'label' => 'No inventory data'];
        }

        if ($targetValue === null || $baseline === null) {
            return ['key' => 'incomplete', 'label' => 'Add baseline & target'];
        }

        // A target at or above baseline implies no reduction — almost always a
        // data-entry slip. Flag it rather than reporting a false "achieved".
        if ($targetValue >= $baseline) {
            return ['key' => 'incomplete', 'label' => 'Check baseline & target'];
        }

        if ($current <= $targetValue) {
            return ['key' => 'achieved', 'label' => 'Target achieved'];
        }

        $baseYear = $target->base_year !== null ? (int) $target->base_year : null;
        $targetYear = (int) $target->target_year;

        if ($targetYear < $fiscalYear) {
            return ['key' => 'missed', 'label' => 'Target year passed'];
        }

        // Without a base year, or before it, there is no line to measure against.
        if ($baseYear === null || $baseYear >= $targetYear || $fiscalYear <= $baseYear) {
            return ['key' => 'in_progress', 'label' => 'In progress'];
        }

        $elapsed = ($fiscalYear - $baseYear) / ($targetYear - $baseYear);
        $expected = $baseline - (($baseline - $targetValue) * $elapsed);

        // 2% tolerance so a marginal miss does not read as a red failure.
        if ($current <= $expected * 1.02) {
            return ['key' => 'on_track', 'label' => 'On track'];
        }

        return ['key' => 'off_track', 'label' => 'Behind schedule'];
    }
}
