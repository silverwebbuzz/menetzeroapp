<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\ClimateOpportunity;
use Illuminate\Http\Request;

class ClimateOpportunityController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.climate-opportunities.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'opportunities' => ClimateOpportunity::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'potential_impact' => 'nullable|string|max:2000',
            'actions' => 'nullable|string|max:5000',
        ]);

        ClimateOpportunity::create(array_merge($validated, [
            'company_id' => $company->id,
            'fiscal_year' => $fiscalYear,
        ]));

        return $this->fiscalRedirect('disclosures.climate-opportunities.index', $fiscalYear, 'Opportunity added.');
    }

    public function update(Request $request, ClimateOpportunity $climateOpportunity)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($climateOpportunity, $company->id, $fiscalYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'potential_impact' => 'nullable|string|max:2000',
            'actions' => 'nullable|string|max:5000',
        ]);

        $climateOpportunity->update($validated);

        return $this->fiscalRedirect('disclosures.climate-opportunities.index', $fiscalYear, 'Opportunity updated.');
    }

    public function destroy(Request $request, ClimateOpportunity $climateOpportunity)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($climateOpportunity, $company->id, $fiscalYear);

        $climateOpportunity->delete();

        return $this->fiscalRedirect('disclosures.climate-opportunities.index', $fiscalYear, 'Opportunity removed.');
    }

    protected function assertOwned(ClimateOpportunity $item, int $companyId, int $fiscalYear): void
    {
        if ($item->company_id !== $companyId || $item->fiscal_year !== $fiscalYear) {
            abort(404);
        }
    }
}
