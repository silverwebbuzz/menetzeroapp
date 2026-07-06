<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\SupplyChainSupplier;
use Illuminate\Http\Request;

class SupplyChainSupplierController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        $suppliers = SupplyChainSupplier::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('supplier_name')
            ->get();

        return view('disclosures.supply-chain.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'suppliers' => $suppliers,
            'totalSpend' => $suppliers->sum('spend_aed'),
            'screenedCount' => $suppliers->whereIn('screening_status', ['passed', 'in_progress'])->count(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        SupplyChainSupplier::create(array_merge(
            $this->validateSupplier($request),
            ['company_id' => $company->id, 'fiscal_year' => $fiscalYear]
        ));

        return $this->fiscalRedirect('disclosures.supply-chain.index', $fiscalYear, 'Supplier added.');
    }

    public function update(Request $request, SupplyChainSupplier $supplyChainSupplier)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($supplyChainSupplier, $company->id, $fiscalYear);

        $supplyChainSupplier->update($this->validateSupplier($request));

        return $this->fiscalRedirect('disclosures.supply-chain.index', $fiscalYear, 'Supplier updated.');
    }

    public function destroy(Request $request, SupplyChainSupplier $supplyChainSupplier)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($supplyChainSupplier, $company->id, $fiscalYear);

        $supplyChainSupplier->delete();

        return $this->fiscalRedirect('disclosures.supply-chain.index', $fiscalYear, 'Supplier removed.');
    }

    protected function validateSupplier(Request $request): array
    {
        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'category' => 'required|in:goods,services,capital',
            'spend_aed' => 'nullable|numeric|min:0',
            'country' => 'nullable|string|max:100',
            'scope3_category' => 'nullable|integer|min:1|max:15',
            'screening_status' => 'nullable|in:not_screened,in_progress,passed,failed',
            'human_rights_assessed' => 'nullable|boolean',
            'environmental_assessed' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['human_rights_assessed'] = $request->boolean('human_rights_assessed');
        $validated['environmental_assessed'] = $request->boolean('environmental_assessed');
        $validated['scope3_category'] = (int) ($validated['scope3_category'] ?? 1);
        $validated['screening_status'] = $validated['screening_status'] ?? 'not_screened';

        return $validated;
    }

    protected function assertOwned(SupplyChainSupplier $record, int $companyId, int $fiscalYear): void
    {
        if ($record->company_id !== $companyId || $record->fiscal_year !== $fiscalYear) {
            abort(404);
        }
    }
}
