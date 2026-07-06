<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\EsgSustainabilityTarget;
use Illuminate\Http\Request;

class EsgSustainabilityTargetController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.esg-targets.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'targets' => EsgSustainabilityTarget::where('company_id', $company->id)
                ->orderBy('target_year')
                ->orderBy('target_category')
                ->get(),
            'climateTargets' => \App\Models\ReductionTarget::where('company_id', $company->id)
                ->where('status', 'active')
                ->orderBy('target_year')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        EsgSustainabilityTarget::create(array_merge(
            $this->validateTarget($request),
            ['company_id' => $company->id, 'status' => $request->input('status', 'active')]
        ));

        return $this->fiscalRedirect('disclosures.esg-targets.index', $fiscalYear, 'ESG target saved.');
    }

    public function update(Request $request, EsgSustainabilityTarget $esgSustainabilityTarget)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($esgSustainabilityTarget, $company->id);

        $esgSustainabilityTarget->update($this->validateTarget($request));

        return $this->fiscalRedirect('disclosures.esg-targets.index', $fiscalYear, 'ESG target updated.');
    }

    public function destroy(Request $request, EsgSustainabilityTarget $esgSustainabilityTarget)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($esgSustainabilityTarget, $company->id);

        $esgSustainabilityTarget->delete();

        return $this->fiscalRedirect('disclosures.esg-targets.index', $fiscalYear, 'ESG target removed.');
    }

    protected function validateTarget(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'target_category' => 'required|in:water,waste,energy,diversity,social,governance,other',
            'metric_label' => 'nullable|string|max:255',
            'baseline_value' => 'nullable|numeric',
            'target_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:32',
            'base_year' => 'nullable|integer|min:1990|max:2100',
            'target_year' => 'required|integer|min:2000|max:2100',
            'status' => 'nullable|in:active,draft,achieved,retired',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    protected function assertOwned(EsgSustainabilityTarget $target, int $companyId): void
    {
        if ($target->company_id !== $companyId) {
            abort(404);
        }
    }
}
