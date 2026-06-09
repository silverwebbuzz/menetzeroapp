<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class OverviewController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function hub(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.hub', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            's2Completeness' => $this->disclosureService->completenessS2($company->id, $fiscalYear),
            's1Completeness' => $this->disclosureService->completenessS1($company->id, $fiscalYear),
        ]);
    }

    public function s2(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'ifrs_s2',
            'completeness' => $this->disclosureService->completenessS2($company->id, $fiscalYear),
        ]);
    }

    public function s1(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.s1-overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'ifrs_s1',
            'completeness' => $this->disclosureService->completenessS1($company->id, $fiscalYear),
        ]);
    }
}
