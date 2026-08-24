<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\ReductionTarget;
use App\Models\TransitionAction;
use App\Services\ReductionTargetProgressService;
use Illuminate\Http\Request;

class ReductionTargetController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.targets.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'targets' => ReductionTarget::where('company_id', $company->id)
                ->with('transitionActions')
                ->orderBy('target_year')
                ->get(),
            // Keyed by target id so the list can show the same progress figures
            // as the ESG dashboard without duplicating the comparison logic.
            'progress' => collect(app(ReductionTargetProgressService::class)->build($company, $fiscalYear))
                ->keyBy('id')
                ->all(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $validated = $this->validateTarget($request);
        $target = ReductionTarget::create(array_merge($validated, [
            'company_id' => $company->id,
            'status' => $validated['status'] ?? 'active',
        ]));

        $this->syncActions($target, $company->id, $request->input('actions', []));

        return $this->fiscalRedirect(
            'disclosures.s2.targets.index',
            $fiscalYear,
            $this->baselineWarning($validated, $company->id) ?? 'Reduction target saved.'
        );
    }

    public function update(Request $request, ReductionTarget $reductionTarget)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($reductionTarget, $company->id);

        $validated = $this->validateTarget($request);
        $reductionTarget->update($validated);
        $this->syncActions($reductionTarget, $company->id, $request->input('actions', []));

        return $this->fiscalRedirect(
            'disclosures.s2.targets.index',
            $fiscalYear,
            $this->baselineWarning($validated, $company->id) ?? 'Reduction target updated.'
        );
    }

    public function destroy(Request $request, ReductionTarget $reductionTarget)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($reductionTarget, $company->id);

        $reductionTarget->delete();

        return $this->fiscalRedirect('disclosures.s2.targets.index', $fiscalYear, 'Reduction target removed.');
    }

    protected function validateTarget(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:absolute,intensity',
            'scope_coverage' => 'required|in:scope1,scope2,scope12,scope3,scope123',
            'base_year' => 'nullable|integer|min:1990|max:2100',
            'target_year' => 'required|integer|min:2000|max:2100',
            'baseline_tco2e' => 'nullable|numeric|min:0',
            'target_tco2e' => 'nullable|numeric|min:0',
            'reduction_percent' => 'nullable|numeric|min:0|max:100',
            'sbti_aligned' => 'nullable|boolean',
            'status' => 'nullable|in:active,draft,achieved,retired',
        ]);
        $validated['sbti_aligned'] = $request->boolean('sbti_aligned');

        // A target year at or before the base year can never show progress.
        if (!empty($validated['base_year']) && $validated['target_year'] <= $validated['base_year']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_year' => 'Target year must be after the base year.',
            ]);
        }

        // A target at or above baseline implies no reduction — almost always a
        // slip between the two fields.
        if (isset($validated['baseline_tco2e'], $validated['target_tco2e'])
            && $validated['baseline_tco2e'] > 0
            && $validated['target_tco2e'] >= $validated['baseline_tco2e']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_tco2e' => 'Target emissions must be lower than the baseline.',
            ]);
        }

        return $validated;
    }

    /**
     * Warn when a target cannot yet show progress — no baseline figure, or a
     * base year with no inventory behind it. Saved anyway (a company may set
     * targets before entering data), but the user is told why the chart will
     * be empty rather than discovering it later.
     */
    protected function baselineWarning(array $data, int $companyId): ?string
    {
        $hasBaselineFigure = !empty($data['baseline_tco2e'])
            || !empty($data['reduction_percent']);

        if (empty($data['base_year'])) {
            return 'Target saved. Set a base year to track progress against it.';
        }

        if (!$hasBaselineFigure) {
            $hasInventory = \App\Models\Measurement::whereHas(
                'location',
                fn ($q) => $q->where('company_id', $companyId)
            )->where('fiscal_year', (int) $data['base_year'])->exists();

            if (!$hasInventory) {
                return 'Target saved, but ' . $data['base_year'] . ' has no emissions data yet — '
                    . 'add a baseline figure or enter that year\'s inventory to track progress.';
            }
        }

        return null;
    }

    protected function syncActions(ReductionTarget $target, int $companyId, array $actions): void
    {
        $target->transitionActions()->delete();

        foreach ($actions as $row) {
            if (empty(trim((string) ($row['title'] ?? '')))) {
                continue;
            }

            TransitionAction::create([
                'reduction_target_id' => $target->id,
                'company_id' => $companyId,
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'action_type' => $row['action_type'] ?? null,
                'planned_year' => !empty($row['planned_year']) ? (int) $row['planned_year'] : null,
                'capex_aed' => $row['capex_aed'] ?? null,
                'opex_aed' => $row['opex_aed'] ?? null,
                'expected_reduction_tco2e' => $row['expected_reduction_tco2e'] ?? null,
                'status' => $row['status'] ?? 'planned',
            ]);
        }
    }

    protected function assertOwned(ReductionTarget $target, int $companyId): void
    {
        if ($target->company_id !== $companyId) {
            abort(404);
        }
    }
}
