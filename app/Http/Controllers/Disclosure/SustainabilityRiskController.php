<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\SustainabilityRisk;
use Illuminate\Http\Request;

class SustainabilityRiskController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $topics = config('disclosure.ifrs_s1.material_topics', []);

        return view('disclosures.sustainability-risks.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'topics' => $topics,
            'risks' => SustainabilityRisk::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->orderBy('topic')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'topic' => 'required|string|max:50',
            'time_horizon' => 'required|in:short,medium,long',
            'description' => 'nullable|string|max:5000',
            'financial_impact' => 'nullable|string|max:2000',
            'likelihood' => 'nullable|in:low,medium,high',
            'mitigation' => 'nullable|string|max:5000',
            'owner' => 'nullable|string|max:255',
            'status' => 'nullable|in:open,monitoring,closed',
        ]);

        SustainabilityRisk::create(array_merge($validated, [
            'company_id' => $company->id,
            'fiscal_year' => $fiscalYear,
            'status' => $validated['status'] ?? 'open',
        ]));

        return $this->fiscalRedirect('disclosures.s1.sustainability-risks.index', $fiscalYear, 'Sustainability risk added.');
    }

    public function update(Request $request, SustainabilityRisk $sustainabilityRisk)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($sustainabilityRisk, $company->id, $fiscalYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'topic' => 'required|string|max:50',
            'time_horizon' => 'required|in:short,medium,long',
            'description' => 'nullable|string|max:5000',
            'financial_impact' => 'nullable|string|max:2000',
            'likelihood' => 'nullable|in:low,medium,high',
            'mitigation' => 'nullable|string|max:5000',
            'owner' => 'nullable|string|max:255',
            'status' => 'nullable|in:open,monitoring,closed',
        ]);

        $sustainabilityRisk->update($validated);

        return $this->fiscalRedirect('disclosures.s1.sustainability-risks.index', $fiscalYear, 'Sustainability risk updated.');
    }

    public function destroy(Request $request, SustainabilityRisk $sustainabilityRisk)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($sustainabilityRisk, $company->id, $fiscalYear);

        $sustainabilityRisk->delete();

        return $this->fiscalRedirect('disclosures.s1.sustainability-risks.index', $fiscalYear, 'Sustainability risk removed.');
    }

    protected function assertOwned(SustainabilityRisk $risk, int $companyId, int $fiscalYear): void
    {
        if ($risk->company_id !== $companyId || $risk->fiscal_year !== $fiscalYear) {
            abort(404);
        }
    }
}
