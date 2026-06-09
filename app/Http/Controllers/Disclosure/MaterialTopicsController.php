<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class MaterialTopicsController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function edit(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.material-topics', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'topics' => $this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear),
        ]);
    }

    public function update(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->disclosureService->syncMaterialTopics(
            $company->id,
            $fiscalYear,
            $request->input('topics', [])
        );

        return $this->fiscalRedirect('disclosures.s1.material-topics', $fiscalYear, 'Material topics saved.');
    }
}
