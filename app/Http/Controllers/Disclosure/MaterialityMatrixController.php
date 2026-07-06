<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class MaterialityMatrixController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $topics = $this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear);

        return view('disclosures.materiality-matrix.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'topics' => $topics,
            'levels' => \App\Models\MaterialSustainabilityTopic::MATERIALITY_LEVELS,
        ]);
    }

    public function update(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->disclosureService->syncMaterialityMatrix(
            $company->id,
            $fiscalYear,
            $request->input('topics', [])
        );

        return $this->fiscalRedirect('disclosures.materiality-matrix.index', $fiscalYear, 'Materiality matrix saved.');
    }
}
