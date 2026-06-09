<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\ClimateRisk;
use Illuminate\Http\Request;

class ClimateRiskController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.climate-risks.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'risks' => ClimateRisk::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->orderBy('risk_type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'risk_type' => 'required|in:physical,transition',
            'time_horizon' => 'required|in:short,medium,long',
            'description' => 'nullable|string|max:5000',
            'financial_impact' => 'nullable|string|max:2000',
            'likelihood' => 'nullable|in:low,medium,high',
            'mitigation' => 'nullable|string|max:5000',
            'owner' => 'nullable|string|max:255',
            'status' => 'nullable|in:open,monitoring,closed',
        ]);

        ClimateRisk::create(array_merge($validated, [
            'company_id' => $company->id,
            'fiscal_year' => $fiscalYear,
            'status' => $validated['status'] ?? 'open',
        ]));

        return $this->fiscalRedirect('disclosures.climate-risks.index', $fiscalYear, 'Climate risk added.');
    }

    public function update(Request $request, ClimateRisk $climateRisk)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($climateRisk, $company->id, $fiscalYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'risk_type' => 'required|in:physical,transition',
            'time_horizon' => 'required|in:short,medium,long',
            'description' => 'nullable|string|max:5000',
            'financial_impact' => 'nullable|string|max:2000',
            'likelihood' => 'nullable|in:low,medium,high',
            'mitigation' => 'nullable|string|max:5000',
            'owner' => 'nullable|string|max:255',
            'status' => 'nullable|in:open,monitoring,closed',
        ]);

        $climateRisk->update($validated);

        return $this->fiscalRedirect('disclosures.climate-risks.index', $fiscalYear, 'Climate risk updated.');
    }

    public function destroy(Request $request, ClimateRisk $climateRisk)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($climateRisk, $company->id, $fiscalYear);

        $climateRisk->delete();

        return $this->fiscalRedirect('disclosures.climate-risks.index', $fiscalYear, 'Climate risk removed.');
    }

    protected function assertOwned(ClimateRisk $risk, int $companyId, int $fiscalYear): void
    {
        if ($risk->company_id !== $companyId || $risk->fiscal_year !== $fiscalYear) {
            abort(404);
        }
    }
}
