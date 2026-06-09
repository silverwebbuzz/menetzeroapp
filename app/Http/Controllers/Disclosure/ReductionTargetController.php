<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\ReductionTarget;
use App\Models\TransitionAction;
use Illuminate\Http\Request;

class ReductionTargetController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.s2.targets.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'targets' => ReductionTarget::where('company_id', $company->id)
                ->with('transitionActions')
                ->orderBy('target_year')
                ->get(),
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

        return $this->fiscalRedirect('disclosures.s2.targets.index', $fiscalYear, 'Reduction target saved.');
    }

    public function update(Request $request, ReductionTarget $reductionTarget)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($reductionTarget, $company->id);

        $validated = $this->validateTarget($request);
        $reductionTarget->update($validated);
        $this->syncActions($reductionTarget, $company->id, $request->input('actions', []));

        return $this->fiscalRedirect('disclosures.s2.targets.index', $fiscalYear, 'Reduction target updated.');
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

        return $validated;
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
